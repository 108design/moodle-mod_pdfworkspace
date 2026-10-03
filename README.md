# PDF Workspace for Moodle

Read, annotate and discuss PDFs within a Moodle course activity. Teachers can
bring several documents together in one workspace, and participants can add
markings and comments with the audiences allowed by the activity settings.

## Features

- Multiple PDFs in one activity, with a tab for each document.
- Drawing, highlighting, text, pin and area markings, with optional comment threads.
- Configurable visibility for participant and staff annotations.
- An overview of questions, answers, your own posts and reports.
- Downloads of original PDFs, comments, annotated PDFs and selected workspace documents.

The plugin targets Moodle 4.5 and later; check your required workflows with your
Moodle version and theme before production use.

## Installation

1. Install the plugin as `mod/pdfworkspace` below Moodle's plugin directory.
2. Complete installation through **Site administration → Notifications**.
3. Add a **PDF Workspace** activity to a course and upload the PDFs.
4. Configure annotation audiences and the downloads available to participants.

For Moodle installations using the split web directory, the plugin belongs in
`public/mod/pdfworkspace`. See Moodle's
[plugin installation guide](https://docs.moodle.org/en/Installing_contributed_modules_or_plugins).

## Working with documents and comments

Choose a document tab, select an annotation tool and mark the PDF. A marking can
stand on its own or start a comment thread. The activity settings control which
audiences participants and staff may select. Replies keep the thread's audience.

Teachers with course editing permission can change the displayed document names.
A PDF with existing annotations is protected from replacement or removal, so
comments keep referring to the document on which they were made.

## Downloads and activity settings

The viewer's download menu separates the current PDF from the entire workspace.
Original PDFs and comments-only downloads are available separately from combined
PDFs containing visible markings and comments.

In the activity settings, use the PDF list to select documents for workspace
downloads and arrange their download order. This order is independent of the
viewer tabs. Initial PDFs are included; PDFs uploaded later start excluded and
can be selected after saving. Document names become bookmarks in the combined PDF.

The capabilities `mod/pdfworkspace:downloadworkspace` and
`mod/pdfworkspace:downloadworkspacecomments` control the two workspace download
variants independently. Participants also need the corresponding download option
enabled in the activity. Teachers with the corresponding permission can download
without that participant option; an explicit permission prohibition still applies.
Commented exports contain only markings and comments visible to the requesting user.

## Server requirements for combined downloads

Combined PDF and workspace downloads additionally require **Python 3.11+ on the
Moodle server**, with **pypdf, ReportLab and cryptography for AES-encrypted PDFs**.
Install the pinned packages from [export/requirements.txt](export/requirements.txt)
in a separate virtual environment outside the public web directory, and configure
its interpreter in the PDF Workspace site-administration settings.

PHP must allow `proc_open` and access to the interpreter path. The Moodle service
account needs permission to run it. Python and these libraries are installed
separately and are not included in the plugin ZIP. The normal viewer, original-PDF
and comments-only downloads work without Python.

See [server setup in English](export/README.md) or
[Servereinrichtung auf Deutsch](export/README.de.md) for the complete instructions.

## Maintainer and origin

PDF Workspace is an independently maintained derivative of RWTH Aachen's PDF
Annotator. Original authorship and copyright notices are retained.
Workspace changes are copyright 2026 Andreas Giesen <andreas@108design.com> (108design).
Maintained by Andreas Giesen.

## License

GNU General Public License version 3 or later. See [LICENSE.md](LICENSE.md) for the full terms.
Bundled third-party code retains its original licences: MIT for pdf-annotate.js,
text-clipper and jsPDF; Apache 2.0 for PDF.js and its supporting assets.
See [thirdpartylibs.xml](thirdpartylibs.xml) and the bundled licence files for details.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
