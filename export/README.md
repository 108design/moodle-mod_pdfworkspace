# Combined PDF download

PDF Workspace integration documentation. Copyright 2026 Andreas Giesen
<andreas@108design.com>. GPL v3 or later.

The third toolbar download is generated on the Moodle server. `download_annotated.php`
checks the requesting user's access, filters every annotation and comment, then invokes
`combined_pdf.py`. The helper copies the original PDF pages, merges vector markings and
adds visible comments as native PDF text notes. The original stored file is not changed.

`download_workspace.php` uses the same runtime to combine the teacher-selected PDFs
in their configured download order, either as original pages or with the requesting
user's visible markings and comments. Both routes share `classes/pdf_export.php`.
The helper accepts a filtered document manifest and adds document bookmarks, while
keeping the original single-PDF JSON interface available. No extra dependencies are needed.

## Server prerequisites

The ordinary viewer, original-PDF and comments-only downloads do not need Python.
Combined PDF and workspace downloads use Python 3.11+ with pypdf, ReportLab and the cryptography AES backend.
`requirements.txt` installs the pinned pypdf and ReportLab versions; the `crypto` extra
installs the AES dependency. The operating system must provide Python and its `venv`
module (for example, `python3-venv` on Debian/Ubuntu).

Create a dedicated virtual environment **outside every public web root**. Do not
include the interpreter, virtual environment or its libraries in the Moodle plugin
ZIP. Interpreters and native dependencies depend on the target system, and virtual
environments must be recreated rather than copied between servers.

Use `--copies` so the interpreter stays inside the Venv. With PHP `open_basedir`,
a symlink to `/usr/bin/python` can fail the plugin's file/access checks. The Venv
path must be within PHP's allowed directories; do not loosen that restriction globally.

Example commands for a server administrator; adapt both absolute paths to your site:

```sh
python3 -m venv --copies /srv/pdfworkspace-export/venv
/srv/pdfworkspace-export/venv/bin/python -m pip install -r /absolute/path/to/moodle/mod/pdfworkspace/export/requirements.txt
/srv/pdfworkspace-export/venv/bin/python -m pip check
```

Run these as the account that maintains the environment. The Moodle PHP service
account needs directory traversal, read access to the libraries and helper, and
execute access to the interpreter. It also needs write access to Moodle's temporary
directory. PHP must permit `proc_open` and access to the interpreter path, including
any `open_basedir` restrictions. The helper has a 120 second limit per request.

Configure **Site administration → Plugins → Activity modules → PDF Workspace →
Python for combined PDF export** with the full interpreter path, for example
`/srv/pdfworkspace-export/venv/bin/python`. It is stored as
`mod_pdfworkspace/exportpython`. For CLI administration, run Moodle's `admin/cli/cfg.php`
as the Moodle service account:

```sh
php admin/cli/cfg.php --component=mod_pdfworkspace --name=exportpython --set=/srv/pdfworkspace-export/venv/bin/python
```

The third button is offered when the configured executable and PHP process support
are available and the existing download permission allows it. Verify library imports
as the **same service account** before trying a download:

```sh
/srv/pdfworkspace-export/venv/bin/python -c 'import pypdf, reportlab, cryptography; from cryptography.hazmat.primitives.ciphers import Cipher, algorithms; print("PDF export dependencies available")'
```

After plugin updates, review `requirements.txt` and install any dependency changes in
this separate environment. Back up the plugin setting and keep a record of installed
dependency versions. A plugin reinstall alone does not install Python libraries.

## Encrypted source PDFs

An encrypted PDF that opens without a password is accepted when decryption with an
empty password succeeds. pypdf's `is_encrypted` flag stays true after successful
decryption, so it is not used as the final rejection condition. PDFs requiring an
opening password remain unsupported; the plugin has no password-entry feature.
AES requires the cryptography backend described above. See the
[pypdf encryption guide](https://pypdf.readthedocs.io/en/6.10.0/user/encryption-decryption.html).

Native PDF notes contain plain text, author and date. Rich comment formatting and
embedded images are not reproduced in the native note; the separate comments PDF/CSV
downloads remain available. The script uses DejaVu Sans (or Liberation Sans) from the
operating system for textbox Unicode when present, otherwise Helvetica.

For a functional export check, run `tests/combined_export_test.py` with the same Python environment. It verifies
selectable source text, vector overlays, native notes, crop offsets and rotation.
