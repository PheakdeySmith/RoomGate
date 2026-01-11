from pathlib import Path
import re


FILES = [
    Path(r"C:\laragon\www\module-test\Modules\Core\resources\views\dashboard\app-invoice-add.html"),
    Path(r"C:\laragon\www\module-test\Modules\Core\resources\views\dashboard\app-invoice-edit.html"),
    Path(r"C:\laragon\www\module-test\Modules\Core\resources\views\dashboard\app-invoice-list.html"),
    Path(r"C:\laragon\www\module-test\Modules\Core\resources\views\dashboard\app-invoice-preview.html"),
    Path(r"C:\laragon\www\module-test\Modules\Core\resources\views\dashboard\app-invoice-print.html"),
]


def sub_assets(value: str) -> str:
    return re.sub(r"(?:\./|\.\./)+assets/", "{{ asset('assets/assets') }}/", value)


def extract_between(text: str, start: str, end: str) -> str:
    start_index = text.index(start) + len(start)
    end_index = text.index(end, start_index)
    return text[start_index:end_index]


for path in FILES:
    text = path.read_text(encoding="utf-8")

    title_match = re.search(r"<title>(.*?)</title>", text, re.S)
    title = title_match.group(1).strip() if title_match else "Dashboard"

    content = extract_between(text, "<!-- Content -->", "<!-- / Content -->").strip()
    content = sub_assets(content)

    css_block = ""
    vendor_css = re.search(r"<!-- Vendors CSS -->(.*?)<!-- Page CSS -->", text, re.S)
    page_css = re.search(r"<!-- Page CSS -->(.*?)<!-- Helpers -->", text, re.S)
    css_parts = []
    if vendor_css:
        css_parts.extend(re.findall(r"<link[^>]+>", vendor_css.group(1)))
    if page_css:
        css_parts.extend(re.findall(r"<link[^>]+>", page_css.group(1)))
    css_links = "\n".join(sub_assets(link.strip()) for link in css_parts).strip()
    if css_links:
        css_block = "@push('page-styles')\n  " + css_links.replace("\n", "\n  ") + "\n@endpush\n"

    scripts_block = ""
    vendor_js = re.search(r"<!-- Vendors JS -->(.*?)<!-- Main JS -->", text, re.S)
    page_js = re.search(r"<!-- Page JS -->(.*?)</body>", text, re.S)
    script_parts = []
    if vendor_js:
        script_parts.extend(re.findall(r"<script[^>]+></script>", vendor_js.group(1)))
    if page_js:
        script_parts.extend(re.findall(r"<script[^>]+></script>", page_js.group(1)))
    scripts = "\n".join(sub_assets(script.strip()) for script in script_parts).strip()
    if scripts:
        scripts_block = "@push('page-scripts')\n  " + scripts.replace("\n", "\n  ") + "\n@endpush\n"

    blade = (
        "@extends('core::components.layouts.master')\n"
        + "@section('title', '"
        + title.replace("'", "\\'")
        + "')\n"
        + css_block
        + "@section('content')\n"
        + content
        + "\n@endsection\n"
        + scripts_block
    )

    path.with_suffix(".blade.php").write_text(blade, encoding="utf-8")
