# PDF Workspace server setup

PDF Workspace integration documentation. Copyright 2026 Andreas Giesen
<andreas@108design.com>. GPL v3 or later.

## Protected PDF setup

1. The server administrator installs Poppler's `pdftoppm` on the Moodle server.
   On [Debian](https://packages.debian.org/stable/poppler-utils) and
   [Ubuntu](https://packages.ubuntu.com/noble/poppler-utils), it is part of
   `poppler-utils`. Check the installed path and version.

   ```sh
   sudo apt install poppler-utils
   command -v pdftoppm
   pdftoppm -v
   ```

2. The Moodle site administrator enters the absolute path, usually
   `/usr/bin/pdftoppm`, under **Site administration → Plugins → Activity modules →
   PDF Workspace → PDF Renderer for Restricted Documents**
   (`mod_pdfworkspace/restrictedpdftoppm`).
3. Check that the Moodle cron process can run the executable with
   `proc_open`. On Linux with PHP `open_basedir`, check the effective PHP
   settings for access to the executable, Moodle's temporary directory and
   `/dev/null` for the renderer's standard streams. Upload and save a small
   protected PDF in a test activity and open its pages once the background task finishes.

To add a protected PDF, use the normal PDF upload field in the activity settings,
tick **Protect All Newly Uploaded PDFs** and save. The checkbox applies to every
new file in that upload. Save regular and protected files in separate uploads.
Each protected PDF can have up to 500 pages, a source file up to 64 MiB and
page images totalling up to 256 MiB. Moodle and PHP upload limits also apply.

Moodle cron prepares one restricted PDF at a time per site. The activity
settings show pending uploads and conversion errors. The viewer loads the
finished JPEGs one page at a time. The lock in the document
tab identifies a protected PDF. Drawing tools and annotation discussions work in
the viewer. Text selection and PDF text search are unavailable on the page images.
Users can still take screenshots of what they can view.

The two permissions are reserved for site administrators by default. Assign them
independently if another role needs access.

- `mod/pdfworkspace:managerestricteddocuments` allows adding and removing protected PDFs.
- `mod/pdfworkspace:downloadrestricteddocuments` allows downloading their originals,
  independently of the activity's ordinary download setting.

Regular exports, course imports and portable Moodle backups omit protected
documents and their annotations. Include both the Moodle database and its
file storage in server backups.

## Combined PDF downloads

Combined downloads create a new PDF on the Moodle server. They preserve the
original pages and add the requesting user's visible markings and comments.
The stored source PDFs are not changed.

Use the single-document export to save a regular PDF with visible annotations.
Workspace downloads merge selected regular PDFs, with or without annotations,
in the download order configured in the activity settings. Document names become
bookmarks.

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

Run the following as the server account that maintains the environment. Verify that
`python3` is version 3.11 or newer. Adapt both absolute paths to your installation.
With Moodle's split web directory, the plugin path includes `public/mod/pdfworkspace`.

```sh
python3 --version
python3 -m venv --copies /srv/pdfworkspace-export/venv
/srv/pdfworkspace-export/venv/bin/python -m pip install -r /absolute/path/to/moodle/mod/pdfworkspace/export/requirements.txt
/srv/pdfworkspace-export/venv/bin/python -m pip check
```

The Moodle PHP service
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

The combined download options appear when PHP can launch the configured
interpreter and the user's download permissions allow them. Verify library imports
as the **same service account** before trying a download:

```sh
/srv/pdfworkspace-export/venv/bin/python -c 'import pypdf, reportlab, cryptography; from cryptography.hazmat.primitives.ciphers import Cipher, algorithms; print("PDF export dependencies available")'
```

After plugin updates, review `requirements.txt` and install any dependency changes in
this separate environment. Back up the plugin setting and keep a record of installed
dependency versions. A plugin reinstall alone does not install Python libraries.

## Encrypted source PDFs

An encrypted PDF that opens without a password is accepted when decryption with an
empty password succeeds. PDFs requiring an
opening password remain unsupported; the plugin has no password-entry feature.
AES requires the cryptography backend described above. See the
[pypdf encryption guide](https://pypdf.readthedocs.io/en/6.10.0/user/encryption-decryption.html).

Native PDF notes contain plain text, author and date. Rich comment formatting and
embedded images are not reproduced in the native note; the separate comments PDF/CSV
downloads remain available. The script uses DejaVu Sans (or Liberation Sans) from the
operating system for textbox Unicode when present, otherwise Helvetica.

For a functional export check, run `tests/combined_export_test.py` with the same Python environment. It verifies
selectable source text, vector overlays, native notes, crop offsets and rotation.
