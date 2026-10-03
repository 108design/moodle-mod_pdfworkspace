PDF Workspace for Moodle

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

PDF Workspace is a fork of RWTH Aachen's PDF Annotator. The original authorship
and copyright notices are retained in files derived from that project. PDF
Workspace changes are copyright 2026 Andreas Giesen <andreas@108design.com>.
Fork maintainer: Andreas Giesen <andreas@108design.com> (108design).
PDF Workspace is maintained and versioned independently of PDF Annotator.
New plugin files carry that attribution alone; modified files also retain their
original attribution. The plugin's new contributions are GPL v3 or later.

Bundled third-party code keeps its original licence and notices:

- MIT: Instructure's `pdf-annotate.js` code in `shared/index.js`, text-clipper,
  and jsPDF. Their original licence notices remain in the files.
- Apache 2.0: PDF.js 6.3.289 and its supporting assets under `shared/pdfjs/`.
  Adapted PDF.js text-layer styles are identified in `shared/viewer.css`.

See `thirdpartylibs.xml` and the licence files in the bundled PDF.js directories
for the exact third-party components and terms. Unchanged vendor bundles,
fonts, CMaps and binary assets retain their original metadata.

### Installation:

- Unzip and copy "pdfworkspace" folder into Moodle's "mod" folder
- Visit admin page to install module

For further installation instructions please see: <http://docs.moodle.org/en/Installing_contributed_modules_or_plugins>

Combined PDF and whole-workspace downloads additionally require **Python 3.11+ on the Moodle server**
with **pypdf, ReportLab and cryptography (for AES-encrypted PDFs)**. Install the pinned
packages from [`export/requirements.txt`](export/requirements.txt) in a separate virtual
environment outside the public web root, then configure its interpreter in the PDF
Workspace site-administration settings. PHP must allow `proc_open` and the interpreter
path; the Moodle service account needs permission to run it. These prerequisites
are installed separately and are not included in the plugin ZIP.
The normal viewer, original-PDF and comments-only downloads work without Python.
See [server setup (English)](export/README.md) or
[Servereinrichtung (Deutsch)](export/README.de.md) for prerequisites and setup.

### Workspace downloads

The activity settings use one PDF list for removal, inclusion in the workspace download,
and download order. Download order is independent of the viewer tabs. All initial PDFs
are included; PDFs uploaded later start excluded and can be selected after saving.
Documents with existing notes stay protected from removal.

The two rights `mod/pdfworkspace:downloadworkspace` and
`mod/pdfworkspace:downloadworkspacecomments` are independent. Participants also need
the matching activity setting enabled; teachers with the corresponding right do not.
An explicit capability prohibition is respected. The commented export includes only
the requesting user's visible markings and comments. Both variants use the same PDF
selection and order, with document names as PDF bookmarks.

## License

GNU General Public License version 3 or later. See [LICENSE.md](LICENSE.md) for the full terms.
Bundled third-party components retain their respective licences.
