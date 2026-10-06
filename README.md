<p align="center">
  <img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/branding/logo.svg" alt="PDF Workspace logo" width="125" height="125">
</p>

# PDF Workspace

Read, annotate and discuss PDFs within a Moodle course activity. Teachers can
bring several documents together in one workspace, and participants can add
markings and comments with the audiences allowed by the activity settings.

## Screenshots

<details>
<summary>View screenshots (10)</summary>

Click a preview to open the full-size screenshot.

<table>
<tr>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-marker.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-marker.jpg" width="241" height="160" alt="PDF tabs, annotation tools and highlighting"></a><br>
<sub>PDF tabs, annotation tools and highlighting</sub>
</td>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new-comment.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new-comment.jpg" width="276" height="160" alt="Add a comment to a PDF marking"></a><br>
<sub>Add a comment to a PDF marking</sub>
</td>
</tr>
<tr>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new-comment2.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new-comment2.jpg" width="278" height="160" alt="Format comments with bold text and lists"></a><br>
<sub>Format comments with bold text and lists</sub>
</td>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-reply.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-reply.jpg" width="300" height="145" alt="Reply within a comment thread"></a><br>
<sub>Reply within a comment thread</sub>
</td>
</tr>
<tr>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-overview-new.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-overview-new.jpg" width="300" height="148" alt="Compact overview with document links and comment previews"></a><br>
<sub>Compact overview with document links and comment previews</sub>
</td>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-add-documents.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-add-documents.jpg" width="300" height="144" alt="Select workspace documents and download order"></a><br>
<sub>Select workspace documents and download order</sub>
</td>
</tr>
<tr>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-download-options.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-download-options.jpg" width="230" height="160" alt="Current PDF and workspace download options"></a><br>
<sub>Current PDF and workspace download options</sub>
</td>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new1.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new1.jpg" width="277" height="160" alt="Upload PDFs and configure permitted audiences"></a><br>
<sub>Upload PDFs and configure permitted audiences</sub>
</td>
</tr>
<tr>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new2.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-new2.jpg" width="300" height="122" alt="Configure annotation tools and participant downloads"></a><br>
<sub>Configure annotation tools and participant downloads</sub>
</td>
<td align="center" width="50%" valign="middle">
<a href="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-settings.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-mod_pdfworkspace/main/docs/screenshots/pdf-workspace-settings.jpg" width="300" height="148" alt="Site administration and combined-export settings"></a><br>
<sub>Site administration and combined-export settings</sub>
</td>
</tr>
</table>

</details>

## Features

- Multiple PDFs in one activity, with a tab for each document.
- Drawing, highlighting, text, pin and area markings, with optional comment threads.
- Configurable visibility for participant and staff annotations.
- An overview of questions, answers, your own posts and reports.
- Downloads of original PDFs, comments, annotated PDFs and selected workspace documents.

Supports Moodle 4.5, 5.0, 5.1 and 5.2.

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

## Finding questions and replies

The overview brings together questions, answers, your own posts and reported
comments across the workspace. Compact tables show the document first, followed
by a two-line comment preview and author details. Hover over a preview or focus
it with the keyboard to read the full text. Open a document, question or reply
link to jump directly to the corresponding PDF, marking and comment.

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

## Privacy

PDFs, markings, comment content and attachments are stored within Moodle,
with author and selected-recipient references, subscriptions, votes and reports.
The plugin supports Moodle Privacy API export and erasure. Combined downloads
are generated locally on the Moodle server and apply the requesting user's
visibility permissions.

## Maintainer and origin

PDF Workspace is an independently maintained derivative of RWTH Aachen's PDF
Annotator. Original authorship and copyright notices are retained.
Workspace changes are copyright 2026 Andreas Giesen <andreas@108design.com> (108design).
Maintained by Andreas Giesen.

## License

**Available free of charge under the terms of the applicable license.**

GNU General Public License version 3 or later. See [LICENSE.md](https://github.com/108design/moodle-mod_pdfworkspace/blob/main/LICENSE.md) for the full terms.
Bundled third-party code retains its original licences: MIT for pdf-annotate.js,
text-clipper and jsPDF; Apache 2.0 for PDF.js and its supporting assets.
See [thirdpartylibs.xml](thirdpartylibs.xml) and the bundled licence files for details.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
