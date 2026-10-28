"""Drives a real WebsiteBaker in Docker: installs it, uploads the module zip,
saves the settings form and checks the tag on the home page.

Used by scripts/verify-docker.sh. Python 3 standard library only.
Usage: python3 scripts/verify_wb.py <base-url> <module-zip>
"""
import http.cookiejar
import os
import re
import sys
import urllib.parse
import urllib.request
import uuid

BASE = sys.argv[1].rstrip("/")
ZIP = sys.argv[2]
ADMIN_PASSWORD = os.environ.get("WB_TEST_PASSWORD") or ("Test" + uuid.uuid4().hex[:12])
TAG_START = '<script defer src="https://privatusstats.com/js/pa.js"'
failures = 0


def ok(name):
    print("ok   " + name)


def fail(name, detail=""):
    global failures
    failures += 1
    print("FAIL " + name + ("\n  " + detail[:600] if detail else ""))


def check(name, cond, detail=""):
    ok(name) if cond else fail(name, detail)


def session():
    jar = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def get(op, path):
    with op.open(BASE + path, timeout=60) as r:
        return r.read().decode("utf-8", "replace")


def post(op, path, fields, files=None):
    if files:
        boundary = uuid.uuid4().hex
        body = b""
        for k, v in fields:
            body += ("--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n" % (boundary, k, v)).encode()
        for k, (fname, data) in files.items():
            body += ("--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: application/zip\r\n\r\n" % (boundary, k, fname)).encode() + data + b"\r\n"
        body += ("--%s--\r\n" % boundary).encode()
        req = urllib.request.Request(BASE + path, data=body, headers={"Content-Type": "multipart/form-data; boundary=" + boundary})
    else:
        req = urllib.request.Request(BASE + path, data=urllib.parse.urlencode(fields).encode())
    with op.open(req, timeout=120) as r:
        return r.read().decode("utf-8", "replace")


def hidden(html, form_marker=None):
    if form_marker:
        i = html.find(form_marker)
        html = html[i:html.find("</form>", i)] if i >= 0 else ""
    out = []
    for m in re.finditer(r"<input[^>]*type=\"hidden\"[^>]*>", html):
        n = re.search(r'name="([^"]*)"', m.group(0))
        v = re.search(r'value="([^"]*)"', m.group(0))
        if n:
            out.append((n.group(1), v.group(1) if v else ""))
    return out


def head(html):
    m = re.search(r"(?is)<head.*?</head>", html)
    return m.group(0) if m else ""


# 1. Install WebsiteBaker.
admin = session()
page = get(admin, "/install/index.php")
fields = hidden(page, "website_baker_installation_wizard")
fields = [(k, v) for k, v in fields if k not in ("url",)] + [("url", "")]
fields += [
    ("wb_url", BASE + "/"), ("default_language", "EN"), ("database_host", "wbdb"), ("database_name", "wb"),
    ("table_prefix", "wb_"), ("database_username", "wb"), ("database_password", "wbpw"), ("website_title", "Example"),
    ("admin_username", "admin"), ("admin_email", "admin@example.com"), ("admin_password", ADMIN_PASSWORD),
    ("admin_repassword", ADMIN_PASSWORD), ("install", "Install WebsiteBaker"),
]
post(admin, "/install/save.php", fields)

# 2. Log in.
admin = session()
page = get(admin, "/admin/login/index.php")
f = dict(hidden(page))
fields = hidden(page) + [(f["username_fieldname"], "admin"), (f["password_fieldname"], ADMIN_PASSWORD)]
start = post(admin, "/admin/login/index.php", fields)
check("logged in to the backend", "logout" in start.lower(), start[:300])

# 3. Upload the module zip like an admin does.
modules_page = get(admin, "/admin/modules/index.php")
fields = hidden(modules_page, 'id="install"') + [("submit", "Install")]
with open(ZIP, "rb") as fh:
    data = fh.read()
result = post(admin, "/admin/modules/install.php", fields, {"userfile": (ZIP.split("/")[-1], data)})
result_text = re.sub(r"\s+", " ", re.sub(r"(?s)<script.*?</script>|<[^>]+>", " ", result))
check("module zip installs (passes CodeGuard)", "installed successfully" in result_text.lower() or "success" in result_text.lower(), result_text[-800:])

tools = get(admin, "/admin/admintools/index.php")
check("Privatus Analytics shows in Admin-Tools", "privatus_analytics" in tools, re.sub(r"<[^>]+>", " ", tools)[-500:])

# A page to look at, so the home page runs through the normal template.
pages = get(admin, "/admin/pages/index.php")
added = post(admin, "/admin/pages/add.php", hidden(pages, 'id="add"') + [
    ("title", "Home"), ("type", "wysiwyg"), ("parent", "0"), ("visibility", "public"),
    ("admin_groups[]", "1"), ("viewing_groups[]", "1"), ("submit", "Add"),
])
check("a public page is created", "under construction" not in get(session(), "/"))

# 4. No site ID yet, so no tag.
visitor = session()
home = get(visitor, "/")
check("no tag before a site ID is saved", "privatusstats.com" not in home)

# 5. Save settings through the tool form.
tool_path = "/admin/admintools/tool.php?tool=privatus_analytics"
tool = get(admin, tool_path)
check("settings form is shown", 'name="privatus_analytics_site_id"' in tool, re.sub(r"<[^>]+>", " ", tool)[-500:])
fields = hidden(tool, "privatus_analytics_save") or hidden(tool)
fields = [(k, v) for k, v in hidden(tool) if k != "privatus_analytics_save"]
saved = post(admin, tool_path, fields + [
    ("privatus_analytics_save", "1"), ("privatus_analytics_site_id", "pa_Docker123"),
    ("privatus_analytics_modules[]", "engage"), ("privatus_analytics_modules[]", "vitals"),
    ("privatus_analytics_skip_logged_in", "1"),
])
check("settings saved message", "Settings saved." in saved, re.sub(r"<[^>]+>", " ", saved)[-500:])

home = get(visitor, "/")
expected = TAG_START + ' data-site="pa_Docker123" data-modules="engage,vitals"></script>'
check("exact tag is in the head for visitors", expected in head(home), head(home)[-800:])
check("tag is printed once", home.count("privatusstats.com/js/pa.js") == 1)

# 6. Logged-in admin is not counted.
admin_home = get(admin, "/")
check("logged-in admin gets no tag", "privatusstats.com" not in admin_home)

# 7. Forged post without a valid token is ignored.
tool = get(admin, tool_path)
fields = [(k, v if not re.match(r"^[0-9a-f]{16}$", k) else "forged") for k, v in hidden(tool)]
post(admin, tool_path, fields + [("privatus_analytics_save", "1"), ("privatus_analytics_site_id", "pa_Forged")])
check("a post with a bad token is not saved", "pa_Forged" not in get(visitor, "/"))

# 8. A bad site ID is refused and the old one stays.
tool = get(admin, tool_path)
bad = post(admin, tool_path, hidden(tool) + [("privatus_analytics_save", "1"), ("privatus_analytics_site_id", 'pa_x" onload="alert(1)')])
check("bad site ID gets an error", "does not look right" in bad)
home = get(visitor, "/")
check("bad site ID never reaches the page", "onload" not in home and "pa_Docker123" in home)

# 9. Turning off the logged-in skip counts the admin too. All modules off gives an empty list.
tool = get(admin, tool_path)
post(admin, tool_path, hidden(tool) + [("privatus_analytics_save", "1"), ("privatus_analytics_site_id", "pa_Docker123")])
admin_home = get(admin, "/")
check("admin is tracked when the skip box is off, with no modules",
      TAG_START + ' data-site="pa_Docker123" data-modules=""></script>' in head(admin_home), head(admin_home)[-500:])

# 10. Empty site ID removes the tag.
tool = get(admin, tool_path)
post(admin, tool_path, hidden(tool) + [("privatus_analytics_save", "1"), ("privatus_analytics_site_id", "")])
check("clearing the site ID removes the tag", "privatusstats.com" not in get(visitor, "/"))

print("\nAll checks passed." if failures == 0 else "\n%d failed." % failures)
sys.exit(1 if failures else 0)
