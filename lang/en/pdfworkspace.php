<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Language strings definition
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Rabea de Groot, Anna Heynkes, Friederike Schwager
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

$string['actiondropdown'] = "Options";
$string['activities'] = 'Activities';
$string['addAComment'] = 'Add a comment';
$string['add_protected_comment'] = 'Add a comment to private question';
$string['add_private_comment'] = 'Add a comment to personal note';
$string['ago'] = '{$a} ago';
$string['all'] = 'all';
$string['allanswers'] = 'all';
$string['all_answers'] = 'All answers';
$string['allquestions'] = 'all';
$string['all_questions'] = 'All questions';
$string['allquestionsimgtitle'] = "Show all questions in this document";
$string['allquestionstitle'] = 'All questions';
$string['allreports'] = 'all reports';
$string['annotationDeleted'] = 'Marking deleted';
$string['anonymous'] = 'Anonymous';
$string['answer'] = 'Answer';
$string['answerButton'] = 'Answer';
$string['answercounthelpicon'] = 'Number of answers';
$string['answercounthelpicon_help'] = 'This column tells you how many answers a question has received.';
$string['answers'] = 'answers';
$string['answerSolved'] = 'This answer was marked as correct by the manager.';
$string['answerstab'] = 'Answers';
$string['answerstabicon'] = 'Answers';
$string['answerstabicon_help'] = 'Show all answers or just answers to questions you follow in this activity. You automatically follow a question you ask.';
$string['author'] = 'Author';
$string['overviewreport'] = 'Report';
$string['overviewreporter'] = 'Reported by';
$string['average'] = 'average';
$string['average_answers'] = 'Average answers';
$string['average_help'] = 'Only users who wrote at least one comment are included in the calculation of the average (arithmetic mean)';
$string['average_questions'] = 'Average questions';

$string['by'] = 'by';
$string['by_other_users'] = 'by other users';
$string['bynameondate'] = 'by {$a->name} - {$a->date}';

$string['cancelButton'] = 'Cancel';
$string['chart_title'] = 'Questions and answers in this activity';
$string['clicktoopen2'] = 'Click {$a} link to view the file.';
$string['closedquestions'] = 'solved';
$string['colorPicker'] = 'Pick a color';
$string['comment'] = 'Comment';
$string['commentDeleted'] = 'Comment has been deleted';
$string['comments'] = 'Comments';
// Annotation separation.
$string['comments_icon_private'] = 'This comment is private';
$string['comments_icon_public'] = 'This comment is public';
$string['comments_text_all'] = 'Show all comments';
$string['comments_text_private'] = 'Only show private comments (orange)';
$string['comments_text_public'] = 'Only show public comments (blue)';
$string['comments_text_teacher'] = 'Only show comments addressed to teachers';
$string['comments_text_targeted'] = 'Only show comments addressed to one selected student';
$string['configmaxbytes'] = 'Maximum file size';
$string['correct'] = 'correct';
$string['count'] = 'count';
$string['createAnnotation'] = 'Add a marking';
$string['currentPage'] = 'current page number';

$string['day'] = 'day';
$string['days'] = 'days';
$string['decision:overlappingAnnotation'] = 'Several markings overlap here. Choose the one you want.';
$string['decision'] = 'Make a decision';
$string['delete'] = 'Delete';
$string['deleteComment'] = 'Delete comment';
$string['deletedComment'] = 'deleted comment';
$string['deletedQuestion'] = 'deleted question';
$string['deletingAnnotation_manager'] = 'The marking and all its comments will be deleted.';
$string['deletingAnnotation_student'] = "The marking and all its comments will be deleted.<br>You can delete your own markings until someone else replies.";
$string['deletingComment'] = 'The comment will be deleted. It will be displayed as deleted unless it is the last comment in its thread.';
$string['deletingCommentTitle'] = 'Are you sure?';
$string['deletingQuestion_manager'] = 'The comment will be deleted.<br>Hint: If you want to delete all answers as well, delete the marking in the document.';
$string['deletingQuestion_student'] = 'The question will be deleted.<br>If it is not answered, the marking will be deleted too, otherwise the question will be displayed as deleted';
$string['deletionForbidden'] = 'Deletion not allowed';
$string['didyouknow'] = 'Did you know?';
$string['dnduploadpdfworkspace'] = 'Create file for PDF Workspace';
$string['document'] = 'Document';
$string['drawing'] = 'Draw in the document with the pen.';

$string['edit'] = 'Edit';
$string['editAnnotation'] = 'The marking will move. This may change what the question refers to.';
$string['editAnnotationTitle'] = 'Are you sure?';
$string['editButton'] = 'Save';
$string['editedComment'] = 'last edited';
$string['editNotAllowed'] = 'Panning not allowed!';
$string['emptypdf'] = 'This PDF has no comments yet.';
$string['enterText'] = 'Enter text';
$string['entity_helptitle'] = 'Help for';
$string['error:addAnnotation'] = 'The marking could not be added.';
$string['error:addComment'] = 'An error has occurred while adding the comment.';
$string['error:closequestion'] = 'An error has occurred while closing/opening the question.';
$string['error:deleteAnnotation'] = 'The marking could not be deleted.';
$string['error:editAnnotation'] = 'The marking could not be changed.';
$string['error:editcomment'] = 'An error has occurred while trying to edit a comment.';
$string['error:findimage'] = 'An error occurred while trying to find image {$a}.';
$string['error:forwardquestion'] = 'An error has occurred while forwarding the question.';
$string['error:forwardquestionnorecipient'] = 'An error has occurerd while forwarding the question.: No person in this course has the capability to receive forwarded questions.';
$string['error:getAllQuestions'] = 'An error has occurred while getting the questions of this document.';
$string['error:getAnnotation'] = 'The marking could not be loaded.';
$string['error:getAnnotations'] = 'The markings could not be loaded.';
$string['error:getComments'] = 'An error has occurred while getting the comments.';
$string['error:getimageheight'] = 'An error has occurred while getting image height of {$a}.';
$string['error:getimagewidth'] = 'An error has occurred while getting image width of {$a}.';
$string['error:getQuestions'] = 'An error has occurred while getting the questions for this page.';
$string['error:hideComment'] = "An error has occurred while trying to hide the comment from participants' view.";
$string['error:markasread'] = 'The item could not be marked as read.';
$string['error:markasunread'] = 'The item could not be marked as unread.';
$string['error:markcorrectanswer'] = 'An error has occurred while marking the answer as correct.';
$string['error:maximalsizeoffile'] = 'Your file {$a->filename}, because it exceeds {$a->filesize} as the maximum size of files. You can attach file(s) with at most {$a->maxfilesize} to a single comment.';
$string['error:missingAnnotationtype'] = 'This marking type is unavailable.';
$string['error:openingPDF'] = 'An error occurred while opening the PDF file.';
$string['error:openprintview'] = 'An error has occurred while trying to open the pdf in Acrobat Reader.';
$string['error:printcomments'] = 'An error has occurred while trying to open the comments in a pdf.';
$string['error:printcommentsdata'] = 'Error with data from server.';
$string['error:printlatex'] = 'An error has occurred while trying to add a LaTeX formula to the pdf.';
$string['error:redisplayComment'] = 'An error has occurred while redisplaying the comment.';
$string['error:renderPage'] = 'An error has occurred while rendering the page.';
$string['error:reportComment'] = 'An error has occurred while saving the report.';
$string['error:subscribe'] = 'An error has occurred while subscribing to the question.';
$string['error:unsubscribe'] = 'An error has occurred while unsubscribing to the question.';
$string['error:unsupportedextension'] = 'The extension of submitted data is not supported. Please select other extension.';
$string['error:redihideCommentsplayComment'] = 'An error occurred while re-inserting the comment for attendees.';
$string['error:voteComment'] = 'An error has occurred while saving the vote.';
$string['error'] = 'Error!';
$string['eventreport_added'] = 'A comment was reported';
$string['export_comments_csv'] = 'Export as CSV';
$string['export_comments_csv_tooltip'] = 'Download comments as CSV file';
$string['export_comments_pdf'] = 'Export as PDF';
$string['export_comments_pdf_tooltip'] = 'Download comments as PDF file';
$string['combinedexport'] = 'PDF with markings and comments';
$string['combinedexporttooltip'] = 'Download the original PDF with visible markings and PDF comments';
$string['combinedexportpython'] = 'Python for combined PDF export';
$string['combinedexportpython_desc'] = 'Absolute path to an executable Python environment with pypdf and ReportLab installed. The combined download is shown only when this path works.';
$string['combinedexportunavailable'] = 'The combined PDF export is not configured on this server.';
$string['combinedexportfailed'] = 'The combined PDF could not be generated.';
$string['filenotfound'] = 'File not found, sorry.';
$string['forward'] = 'Forward';
$string['forwardedquestionhtml'] = '{$a->sender} forwarded the following question to you: <br /> <br />
        "{$a->questioncontent}" <br /> <br />
        with the message: <br /> <br />
        "{$a->message}" <br /> <br />
        The question is available <a href="{$a->urltoquestion}">here</a>.';
$string['forwardedquestiontext'] = '{$a->sender} forwarded the following question to you:

        "{$a->questioncontent}"

        with the message:

        "{$a->message}"

        The question is available at: {$a->urltoquestion}';
$string['fullscreen'] = 'Fullscreen';
$string['fullscreenBack'] = 'Exit Fullscreen';

$string['global_setting_anonymous'] = 'Allow anonymous posting?';
$string['global_setting_anonymous_desc'] = 'With this option you allow your user to post comments anonymously. This option activates anonymous posting globally';
$string['global_setting_attobuttons'] = 'Atto editor toolbar config';
$string['global_setting_attobuttons_desc'] = 'The list of plugins and the order they are displayed can be configured here. The configuration consists of groups (one per line) followed by the ordered list of plugins for that group. The group is separated from the plugins with an equals sign and the plugins are separated with commas. The group names must be unique and should indicate what the buttons have in common. Button and group names should not be repeated and may only contain alphanumeric characters.';
$string['global_setting_use_studentdrawing'] = 'Allow drawings for participants?';
$string['global_setting_use_studentdrawing_desc'] = 'Choose whether participants may draw on PDFs by default. Drawings can have optional comments.';
$string['global_setting_use_studenttextbox'] = 'Allow textboxes for participants?';
$string['global_setting_use_studenttextbox_desc'] = 'Choose whether participants may add text to PDFs by default. Text boxes can have optional comments.';
$string['global_setting_useprint'] = 'Allow save and print?';
$string['global_setting_useprint_comments'] = 'Allow saving/printing comments?';
$string['global_setting_useprint_comments_desc'] = 'Allow participants to save and print the markings and comments';
$string['global_setting_use_private_comments'] = 'Allow personal notes?';
$string['global_setting_use_private_comments_desc'] = 'Allow participants to write personal markings and personal notes';
$string['global_setting_use_protected_comments'] = 'Allow private comments?';
$string['global_setting_use_protected_comments_desc'] = 'Allow participants to write private markings and private comments. Only author and manager can see this comment.';
$string['global_setting_useprint_desc'] = 'Allow participants to download the original PDF.';
$string['global_setting_useprint_document'] = 'Allow saving/printing document?';
$string['global_setting_useprint_document_desc'] = 'Allow participants to save and print the pdf document';
$string['global_setting_usevotes'] = 'Allow liking of comments?';
$string['global_setting_usevotes_desc'] = 'With this option users can like / vote for posts other than their own.';

$string['hiddenComment'] = 'hidden comment';
$string['hiddenforparticipants'] = 'Hidden from students';
$string['hideAnnotations'] = 'Hide notes and markings';
$string['highlight'] = 'Highlight text and add a comment.';
$string['hour'] = 'hour';
$string['hours'] = 'hours';

$string['in_course'] = 'in this course';
$string['in_document'] = 'in this document';
$string['infonocomments'] = "This document contains no comments at present.";
$string['iscorrecthelpicon'] = 'Correct';
$string['iscorrecthelpicon_help'] = 'When a teacher or manager has marked an answer as correct, a green check mark appears next to it.';
$string['itemsperpage'] = 'Items per page';

$string['justnow'] = 'just now';

$string['lastanswered'] = 'Last Answer';
$string['lastedited'] = 'last edited';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Active';
$string['legacyfilesdone'] = 'Finished';
$string['like'] = 'like';
$string['likeAnswer'] = 'helpful';
$string['likeAnswerForbidden'] = 'already marked as helpful';
$string['likeCountAnswer'] = 'persons think this answer is helpful';
$string['likeCountQuestion'] = 'persons are also interested in this question';
$string['likeForbidden'] = 'You are not allowed to like this comment';
$string['likeOwnComment'] = 'own comment';
$string['likeQuestion'] = 'interesting question';
$string['likeQuestionForbidden'] = 'already marked as helpful';
$string['loading'] = 'Loading!';

$string['markasread'] = 'Mark as read';
$string['markasunread'] = 'Mark as unread';
$string['markCorrect'] = 'Mark as correct';
$string['markhidden'] = 'Hide';
$string['markSolved'] = 'Close question';
$string['markUnsolved'] = 'Reopen question';
$string['maximumfilesize'] = 'Maximum file size';
$string['maximumfilesize_help'] = 'Files uploaded by users may be up to this size.';
$string['me'] = 'me';
$string['messageforwardform'] = 'Your message to the recipient/s';
$string['messageprovider:forwardedquestion'] = 'When a question was forwarded to you';
$string['messageprovider:newanswer'] = 'When a question you subscribed to was answered';
$string['messageprovider:newquestion'] = 'When a new question was asked';
$string['messageprovider:newreport'] = 'When a comment was reported';
$string['min0Chars'] = 'An empty question or comment is not allowed.';
$string['minute'] = 'minute';
$string['minutes'] = 'minutes';
$string['missingAnnotation'] = 'The marking could not be found.';
$string['modifiedby'] = 'by';
$string['modulename'] = 'PDF Workspace';
$string['modulename_help'] = 'Upload one or more PDFs. Participants and teachers can mark pages and share comments with the allowed audiences.';
$string['modulename_link'] = 'mod/pdfworkspace/view';
$string['modulenameplural'] = 'PDF Workspace';
$string['month'] = 'month';
$string['months'] = 'months';
$string['myanswers'] = 'My answers';
$string['mypost'] = 'My post';
$string['myprivate'] = 'My personal notes';
$string['myprotectedanswers'] = 'My private answers';
$string['myprotectedquestions'] = 'My private questions';
$string['mypublicanswers'] = 'My public answers';
$string['mypublicquestions'] = 'My public questions';
$string['myquestion'] = 'Question';
$string['myquestions'] = 'My questions';

$string['newanswerhtml'] = 'Your subscribed question "{$a->question}" was answered by {$a->answeruser} with the comment: <br /> <br /> "{$a->content}"<br /><br />
The answer is <a href="{$a->urltoanswer}">here</a> available.';
$string['newanswertext'] = 'Your subscribed question "{$a->question}" was answered by {$a->answeruser} with the comment:

    "{$a->content}"

The answer is available under: {$a->urltoanswer}';
$string['newquestionhtml'] = 'A new Questions was added by {$a->answeruser} with the content: <br /> <br /> "{$a->content}"<br /><br />
The question is <a href="{$a->urltoanswer}">hier</a> available.';
$string['newquestions'] = 'Recently asked';
$string['newquestiontext'] = 'A new Questions was added by {$a->answeruser} with the content:

    "{$a->content}"

The question is available under: {$a->urltoanswer}';
$string['nextPage'] = 'Next page';
$string['noanswers'] = 'There are no answers in this activity yet.';
$string['noanswerssubscribed'] = 'There are no answers to subscribed questions in this activity yet.';
$string['noCommentsupported'] = 'This kind of marking does not support comments.';
$string['nomyposts'] = 'You have not posted in this activity yet.';
$string['noquestions'] = 'No questions on this page!';
$string['noquestions_overview'] = 'There are no questions in this activity yet.';
$string['noquestions_view'] = 'There are no questions in this document at present.';
$string['noquestionsclosed_overview'] = 'There are no closed questions in this activity yet.';
$string['noquestionsopen_overview'] = 'There are no open questions in this activity yet.';
$string['noreadreports'] = 'There are no read reports in this activity.';
$string['noreports'] = 'There are no reports in this activity yet.';
$string['nosearchresults'] = 'No search results found.';
$string['notificationsubject:forwardedquestion'] = 'Forwarded question in {$a}';
$string['notificationsubject:newanswer'] = 'New answer to subscribed question in {$a}';
$string['notificationsubject:newquestion'] = 'New question in {$a}';
$string['notificationsubject:newreport'] = 'A comment was reported in {$a}';
$string['nounreadreports'] = 'There are no unread reports in this activity.';

$string['on'] = 'on';
$string['onlyDeleteOwnAnnotations'] = ", because it belongs to another user.";
$string['onlyDeleteUncommentedPosts'] = ", because the other users comments would be deleted as well.";
$string['openquestions'] = 'unsolved';
$string['overview'] = 'Overview';
$string['overviewactioncolumn'] = 'Actions';
$string['ownpoststab'] = 'My posts';
$string['ownpoststabicon'] = 'My posts';
$string['ownpoststabicon_help'] = 'This page displays all comments that you posted in this course.';

$string['page'] = 'page';
$string['pdfworkspace:addinstance'] = 'add instance';
$string['pdfworkspace:administrateuserinput'] = 'Administrate comments';
$string['pdfworkspace:closeanyquestion'] = 'Close any question';
$string['pdfworkspace:closequestion'] = 'Close own questions';
$string['pdfworkspace:create'] = 'Create markings and comments';
$string['pdfworkspace:deleteany'] = 'Delete any marking and comment';
$string['pdfworkspace:deleteown'] = 'Delete your own markings and comments';
$string['pdfworkspace:edit'] = 'Edit your own markings and comments';
$string['pdfworkspace:editanypost'] = 'Edit any marking and comment';
$string['pdfworkspace:forwardquestions'] = 'Forward questions';
$string['pdfworkspace:getforwardedquestions'] = 'Receive forwarded questions';
$string['pdfworkspace:hidecomments'] = 'Hide comments for participants';
$string['pdfworkspace:markcorrectanswer'] = 'Mark answers as correct';
$string['pdfworkspace:printcomments'] = 'Download the comments (even if the option is disabled for a PDF Workspace)';
$string['pdfworkspace:printdocument'] = 'Download the document (even if the option is disabled for a PDF Workspace)';
$string['pdfworkspace:recievenewquestionnotifications'] = 'Recieve notifications about new questions';
$string['pdfworkspace:report'] = 'Report inappropriate comments to the course manager';
$string['pdfworkspace:seehiddencomments'] = 'See hidden comments';
$string['pdfworkspace:subscribe'] = 'Subscribe to a question';
$string['pdfworkspace:usedrawing'] = 'Use drawing (even if the option is disabled for a PDF Workspace)';
$string['pdfworkspace:usetextbox'] = 'Use textbox (even if the option is disabled for a PDF Workspace)';
$string['pdfworkspace:view'] = 'View PDF Workspace';
$string['pdfworkspace:viewanswers'] = 'View answers to subscribed questions (overview page)';
$string['pdfworkspace:viewposts'] = 'View own comments (overview page)';
$string['pdfworkspace:viewprotectedcomments'] = 'See private comments';
$string['pdfworkspace:viewquestions'] = 'View open questions (overview page)';
$string['pdfworkspace:viewreports'] = 'View reported comments (overview page)';
$string['pdfworkspace:viewstatistics'] = 'View statistics page';
$string['pdfworkspace:viewteacherstatistics'] = 'See additional information on statistics page';
$string['pdfworkspace:vote'] = "Vote for an interesting question or helpful answer";
$string['pdfworkspace:writeprivatecomments'] = 'Make personal notes';
$string['pdfworkspace:writeprotectedcomments'] = 'Write private comments';
$string['pdfworkspace'] = 'Document';
$string['pdfworkspacecolumn'] = 'Document';
$string['pdfworkspacecontent'] = 'Files and subfolders';
$string['pdfworkspacename'] = 'PDF Workspace';
$string['pdfworkspacepost'] = 'Comments and questions';
$string['pdfButton'] = 'Document';
$string['pluginadministration'] = 'PDF Workspace administration';
$string['aiquestions'] = 'AI Question Analysis';
$string['aiquestions_courselink'] = 'Open Course Settings';
$string['aiquestions_provider'] = 'AI Provider';
$string['aiquestions_provider_desc'] = 'Select a configured text provider instance for question analysis, or use Moodle’s provider order.';
$string['aiquestions_provider_auto'] = 'Moodle Provider Order (Automatic)';
$string['aiquestions_provider_missing'] = 'Selected provider unavailable (ID {$a})';
$string['aiquestions_site_desc'] = 'Allows courses and workspaces to opt in to AI question analysis individually.';
$string['aiquestions_open'] = 'Open AI Analysis';
$string['pdfworkspace:managerestricteddocuments'] = 'Add and remove restricted documents';
$string['pdfworkspace:downloadrestricteddocuments'] = 'Download original PDFs of restricted documents';
$string['restricteddocuments'] = 'Restricted Documents';
$string['restrictedbadge'] = 'Restricted';
$string['restricteduploadlabel'] = 'Protection for New PDFs';
$string['restricteduploadlabel_help'] = 'Restricted PDFs are shown as page images. Downloading the original requires a separate permission.';
$string['restricteduploadcheck'] = 'Protect All Newly Uploaded PDFs';
$string['restricteduploadselectionchanged'] = 'The selection came from an earlier version of this form. Select protection for all new PDFs or reload the activity settings.';
$string['adminhelp_restrictedupload'] = 'Upload Regular and Restricted PDFs';
$string['adminhelp_restrictedupload_help'] = 'The checkbox applies to every file in the upload field above. Upload regular and restricted PDFs separately, saving after each upload.';
$string['adminhelp_restrictedupload_article'] = '<p>Upload the regular PDFs first and save the settings. Open the settings again, upload the restricted PDFs and select <strong>Protect All Newly Uploaded PDFs</strong>. Save again.</p><p>Previously saved PDFs keep their protection status. This option does not apply to files selected through <strong>Select PDFs from the course</strong>.</p>';
$string['restricteddocumenttitle'] = 'Display Name';
$string['restrictedpdf'] = 'PDF File';
$string['restrictedpdf_help'] = 'The server converts the PDF to page images. The maximum file size is 25 MB. The document can have up to 150 pages.';
$string['restrictedadd'] = 'Add Restricted Document';
$string['restricteddownload'] = 'Download Original PDF';
$string['restrictedremove'] = 'Remove Restricted Document';
$string['restrictedremoveconfirm'] = 'Remove “{$a}” and its page preview from this workspace?';
$string['restrictedremoved'] = 'Restricted document removed.';
$string['restrictedpageno'] = 'Page {$a->page} of {$a->total}';
$string['restrictedempty'] = 'This workspace has no restricted documents yet.';
$string['restrictedinvalidtitle'] = 'Enter a display name of no more than 160 characters.';
$string['restrictedinvalidpdf'] = 'Choose a PDF file up to 25 MB.';
$string['restrictedtoomanypages'] = 'The PDF can have up to {$a} pages.';
$string['restrictedrenderfailed'] = 'The page preview could not be created. Check that the PDF opens without a password.';
$string['restrictedrenderer'] = 'PDF Renderer for Restricted Documents';
$string['restrictedrenderer_desc'] = 'Absolute path to the Poppler pdftoppm executable on the Moodle server.';
$string['restrictedrenderersetup'] = 'A site administrator must configure the PDF renderer in the PDF Workspace settings first.';
$string['adminhelp_restricteddocuments'] = 'View Restricted Documents';
$string['adminhelp_restricteddocuments_help'] = 'Pages are displayed as images. The original PDF requires a separate permission.';
$string['adminhelp_restricteddocuments_article'] = '<p>A lock identifies restricted PDFs in the document bar. Use the usual toolbar to mark a page, then select the mark to open its comments. The highlighter still works as a drawing tool. Page images have no selectable text.</p><p>Users with the separate download permission can find the original in the download menu.</p>';
$string['restrictedmarkframe'] = 'Frame';
$string['restrictedmarkpin'] = 'Pin';
$string['restrictedmarkpen'] = 'Pen';
$string['restrictedmarkmarker'] = 'Highlighter';
$string['restrictedmarktext'] = 'Text Box';
$string['restrictedmarkaudience'] = 'Audience';
$string['restrictedmarkrecipient'] = 'Participant';
$string['restrictedmarkremove'] = 'Delete Last Own Marking';
$string['restrictedmarkremoved'] = 'Marking deleted.';
$string['restrictedmarksaved'] = 'Marking saved.';
$string['restrictedmarkerror'] = 'The change could not be saved.';
$string['restrictedmarklimit'] = 'You can save up to 200 of your own markings on this page.';
$string['restrictedmarkforbidden'] = 'You cannot use this tool here.';
$string['restrictedmarkinvalidtext'] = 'Enter up to 160 characters for the text box.';
$string['restrictedmarkthread'] = 'Marking Comments';
$string['restrictedmarkcomment'] = 'Comment';
$string['restrictedmarkpost'] = 'Save Comment';
$string['restrictedmarkcommentsnone'] = 'This marking has no comments yet.';
$string['restrictedmarkinvalidcomment'] = 'Enter a comment of up to 2000 characters.';
$string['privacy:metadata:restrictedmarkings'] = 'Markings on restricted document pages.';
$string['privacy:metadata:restrictedmarkpoints'] = 'Positions relative to the page.';
$string['privacy:metadata:restrictedmarktext'] = 'Content of a text box.';
$string['privacy:metadata:restrictedmarkcomments'] = 'Comments on markings on restricted pages.';
$string['adminhelp_restrictedsetup'] = 'Set Up Restricted Documents';
$string['adminhelp_restrictedsetup_help'] = 'Moodle checks uploads and original downloads using separate permissions.';
$string['adminhelp_restrictedsetup_article'] = '<p>A site administrator configures the path to Poppler’s <code>pdftoppm</code>. Users with permission upload PDFs in the activity settings and select <strong>Protect All Newly Uploaded PDFs</strong>. Copies published elsewhere keep their existing access rules.</p><p>Restricted PDFs and their comments stay out of course imports, workspace exports and portable Moodle backups. Back up the Moodle database and file storage together.</p>';
$string['aiquestions_settings'] = 'Configure AI Question Analysis';
$string['aiquestions_settings_intro'] = 'Question analysis becomes available once it is enabled for both this course and this workspace.';
$string['aiquestions_course'] = 'Allow AI Question Analysis in This Course';
$string['aiquestions_course_help'] = 'Allows each workspace in this course to opt in separately.';
$string['aiquestions_workspace'] = 'Enable AI Question Analysis for This Activity';
$string['aiquestions_workspace_help'] = 'Allows teachers to group open questions from this workspace in the help panel.';
$string['aiquestions_course_required'] = 'Question analysis is available in this workspace once the course allows it.';
$string['aiquestions_panel_intro'] = 'Analysis follows the document and page filters in the overview. Selecting an action sends up to 24 question texts to the configured AI provider.';
$string['aiquestions_topics'] = 'Group Open Questions';
$string['aiquestions_unanswered'] = 'Group Questions Without a Visible Reply';
$string['aiquestions_working'] = 'Analysing questions…';
$string['aiquestions_policy'] = 'Accept Moodle’s AI policy and select the action again.';
$string['aiquestions_unavailable'] = 'Question analysis is currently unavailable. Check the opt-ins and Moodle AI settings.';
$string['aiquestions_empty'] = 'There are no matching questions in this selection.';
$string['aiquestions_failed'] = 'The questions could not be analysed.';
$string['aiquestions_ok'] = 'AI suggestions with links to the original questions.';
$string['aiquestions_limited'] = 'The sample was limited. Follow the links to read the full questions.';
$string['adminhelp_helptab'] = 'Help';
$string['adminhelp_aipilot'] = 'Question Analysis Settings and Data';
$string['adminhelp_aipilot_help'] = 'Question analysis groups visible open questions and links to the original posts.';
$string['adminhelp_aipilot_article'] = '<p>The site administrator allows AI question analysis and chooses its provider. Open <strong>Course → Settings</strong> and opt in under <strong>AI Question Analysis</strong>. Then open the PDF Workspace <strong>Settings</strong> and enable the activity under the same heading. Turning off a higher-level setting pauses the saved opt-ins below it.</p><p>Analysis starts when you select an action. Moodle sends up to 24 question texts to the configured AI provider and stores the prompt and response in the AI subsystem. Private, hidden and deleted questions, PDFs and attachments are excluded. Check the suggested topics against the linked questions.</p>';
$string['privacy:metadata:core_ai'] = 'Question texts and generated topics are processed by the Moodle AI subsystem.';
$string['pluginname'] = 'PDF Workspace';
$string['point'] = 'Add a pin in the document; a comment is optional.';
$string['prevPage'] = 'Previous page';
$string['print'] = 'download document';
$string['printButton'] = 'Download';
$string['printviewtitle'] = 'Comments';
$string['printwithannotations'] = 'download comments';
$string['privacy:metadata:core_files'] = 'PDF Workspace stores uploaded PDFs so people can mark and discuss them.';
$string['privacy:metadata:pdfworkspace_annotations:annotationid'] = 'The ID of the marking that was made. It refers to the data listed above.';
$string['privacy:metadata:pdfworkspace_annotations:userid'] = 'The ID of the user who made this marking.';
$string['privacy:metadata:pdfworkspace_annotations'] = "Information about the markings a user made. This includes the type of marking (e.g. highlight or drawing), its position within a specific file, as well as the time of creation.";
$string['privacy:metadata:pdfworkspace_comments:annotationid'] = 'The ID of the underlying marking.';
$string['privacy:metadata:pdfworkspace_comments:content'] = 'The literal comment.';
$string['privacy:metadata:pdfworkspace_comments:userid'] = "The ID of the comment's author.";
$string['privacy:metadata:pdfworkspace_comments'] = "Information about a user's comments. This includes the content and time of creation of the comment, as well as the underlying marking.";
$string['privacy:metadata:pdfworkspace_reports:commentid'] = 'The ID of the reported comment.';
$string['privacy:metadata:pdfworkspace_reports:message'] = 'The text content of the report.';
$string['privacy:metadata:pdfworkspace_reports:userid'] = 'The author of the report.';
$string['privacy:metadata:pdfworkspace_reports'] = "Users can report other users' comments as inappropriate. These reports stored. This includes the ID of the reported comment as well as the author, content and time of the report.";
$string['privacy:metadata:pdfworkspace_subscriptions:annotationid'] = 'The ID of the question/discussion that was subscribed to.';
$string['privacy:metadata:pdfworkspace_subscriptions:userid'] = 'The ID of the user with this subscription.';
$string['privacy:metadata:pdfworkspace_subscriptions'] = "Information about the subscriptions to individual questions/discussions.";
$string['privacy:metadata:pdfworkspace_votes:commentid'] = "The ID of the comment.";
$string['privacy:metadata:pdfworkspace_votes:userid'] = "The ID of the user who marked the comment as interesting or helpful. It is saved in order to prevent users from voting for the same comment repeatedly.";
$string['privacy:metadata:pdfworkspace_votes'] = "Information about questions and comments that were marked as interesting or helpful.";
$string['private_comments'] = "Personal notes";
$string['private_comments_help'] = 'Visible only for you.';
$string['protected_answers'] = 'Private answers';
$string['protected_comments'] = "Private comments";
$string['protected_comments_help'] = 'Visible only for you and teachers.';
$string['protected_questions'] = 'Private questions';
$string['publicanswers'] = 'Public answers';
$string['public_comments'] = 'Public comments';
$string['publicquestions'] = 'Public questions';

$string['question'] = 'Question';
$string['questionsimgtitle'] = "Show all questions on this page";
$string['questionSolved'] = 'Questions is closed. However, you can still create new comments.';
$string['questionstab'] = 'Questions';
$string['questionstabicon'] = 'Questions';
$string['questionstabicon_help'] = 'This page displays all unsolved questions that were asked in this course. You can also choose to see all or all solved questions in this course.';
$string['questionstitle'] = 'Questions · page';

$string['read'] = 'Read';
$string['reason'] = 'Explanation';
$string['recievenewquestionnotifications'] = 'Notify about new questions';
$string['recipient'] = 'Recipient/s';
$string['recipient_help'] = 'To select several persons, hold down "Ctrl"';
$string['recipientforwardform'] = 'Forward to';
$string['recipientrequired'] = 'Please select recipient/s';
$string['rectangle'] = 'Draw a rectangle in the document; a comment is optional.';
$string['removeCorrect'] = 'Remove marking as correct';
$string['removehidden'] = 'Show';
$string['report'] = 'Report';
$string['reportaddedhtml'] = '{$a->reportinguser} has reported a comment with the message: <br /><br /> "{$a->introduction}"<br /><br />
It is <a href="{$a->urltoreport}">available on the web site</a>.';
$string['reportaddedtext'] = '{$a->reportinguser} has reported a comment with the message:

    "{$a->introduction}"

It is available under: {$a->urltoreport}';
$string['reportedby'] = 'by / on';
$string['reportedcomment'] = 'Reported comment';
$string['reports'] = 'Reported comments';
$string['reportsendbutton'] = 'Send';
$string['reportstab'] = 'Reported comments';
$string['reportstabicon'] = 'Reported comments';
$string['reportstabicon_help'] = 'This page displays comments that were reported as inappropriate in this course. You can choose to see only unread/read* reports or all reports.<br>* Any manager of this course can mark a report as read.';
$string['reportwassentoff'] = 'The comment has been reported.';

$string['search'] = 'Search';
$string['searchresults'] = 'Search results';
$string['second'] = 'second';
$string['seconds'] = 'seconds';
$string['seeabove'] = '';
$string['seenreports'] = 'read only';
$string['send'] = 'Send';
$string['sendAnonymous'] = 'post anonymous';
$string['sendPrivate'] = 'post personal note';
$string['sendProtected'] = 'post private comment';
$string['setting_alternative_name'] = 'Name';
$string['setting_alternative_name_desc'] = 'Name shown for this activity in the course and browser.';
$string['setting_alternative_name_help'] = 'Choose the name shown in the course and browser title.';
$string['setting_anonymous'] = 'Allow anonymous posting?';
$string['setting_fileupload'] = 'PDF documents';
$string['setting_fileupload_help'] = 'Add PDFs to this activity. Documents that already have markings cannot be replaced or removed.';
$string['documentsupload'] = 'PDF documents';
$string['documentsadd'] = 'Add more PDFs';
$string['courseimportsections'] = 'Import Entire Course Sections';
$string['courseimportsections_help'] = 'Select one or more sections. Saving imports all eligible PDFs from those sections.';
$string['courseimportfiles'] = 'Individual PDFs from This Course';
$string['courseimportfiles_help'] = 'Expand a section and tick the PDFs you want to import. The counter here reflects this file selection. Selections under “Import Entire Course Sections” also apply.';
$string['courseimportbrowse'] = 'Select PDFs from the course';
$string['courseimportcount'] = '{count} selected';
$string['courseimportsearch'] = 'Search sections, sources or PDFs';
$string['courseimportnomatch'] = 'No matching PDFs found.';
$string['courseimporthide'] = 'Hide Fully Imported File and Folder Resources';
$string['courseimporthideskipped'] = 'Some sources are still visible because only some of their files were imported, their contents changed, or you lack permission to hide them.';
$string['courseimportunavailable'] = 'At least one selected file or section is no longer available for import. Check your selection.';
$string['adminhelp_courseimport'] = 'Importing PDFs from the course';
$string['adminhelp_courseimport_help'] = 'This option hides File and Folder resources when you import all their files and have permission to hide them. Source workspaces stay visible.';
$string['adminhelp_courseimport_article'] = '<p>Use “Import Entire Course Sections” to find a section directly. Use “Select PDFs from the course” to tick individual files in sections, folders and workspaces. You can combine both selections. A PDF selected in both is imported once.</p><p>Hidden sources and sources with access restrictions are excluded. You can import PDFs from another workspace when you can view the activity and manage its files. Markings and discussions stay in the source workspace. The destination keeps its own copy of the PDF.</p><p>If you use the option to hide sources, source workspaces and folders with other files stay visible. You can show hidden File and Folder resources again from the course menu.</p>';
$string['existingdocuments'] = 'Existing PDFs';
$string['documentlocked'] = 'Locked: this PDF has markings and cannot be removed or replaced.';
$string['removedocument'] = 'Remove this PDF on save';
$string['documentlockedshort'] = 'Cannot be removed';
$string['documentalreadyexists'] = '“{$a}” already exists. Remove the PDF without markings first or choose a different filename.';
$string['editdocumenttitle'] = 'Edit PDF display name';
$string['documenttitle'] = 'PDF display name';
$string['error:documenttitle'] = 'The display name must contain 1 to 80 characters.';
$string['overviewstatus'] = 'Status';
$string['overviewalldocuments'] = 'All PDFs';
$string['applyfilters'] = 'Show';
$string['resetfilters'] = 'Reset';
$string['documentsupload_help'] = 'Add PDFs to this activity. Documents that already have markings cannot be replaced or removed.';
$string['setting_use_studentdrawing'] = "Drawing";
$string['setting_use_studentdrawing_help'] = 'Allow participants to draw on the PDF. A comment is optional.';
$string['setting_use_studenttextbox'] = "Textbox";
$string['setting_use_studenttextbox_help'] = 'Allow participants to add text to the PDF. A comment is optional.';
$string['setting_useprint'] = "save and print";
$string['setting_useprint_comments'] = 'Save and print comments';
$string['setting_useprint_comments_help'] = 'Allow participants to save and print the markings and comments';
$string['setting_usecombineddownload'] = 'Download PDF with comments';
$string['setting_usecombineddownload_help'] = 'Participants can download the original PDF with visible markings and PDF comments. The two separate downloads are controlled independently.';
$string['setting_useprint_document'] = 'Save and print pdf document';
$string['setting_useprint_document_help'] = 'Allow participants to save and print the pdf document';
$string['setting_useprint_help'] = "Please note that drawings are not anonymous and can neither be commented nor reported.";
$string['setting_use_private_comments'] = "Allow personal notes";
$string['setting_use_private_comments_help'] = "Allow participants to write personal notes. Other person cannot see this comment.";
$string['setting_use_protected_comments'] = "Allow private comments";
$string['setting_use_protected_comments_help'] = "Allow participants to write private comments. Only the author and teachers can see this comment.";
$string['setting_usevotes'] = "Votes/Likes";
$string['setting_usevotes_help'] = "With this option enabled, users can like / vote for posts other than their own.";
$string['show'] = 'Show';
$string['showAnnotations'] = 'Show notes and markings';
$string['showless'] = 'less';
$string['showmore'] = 'more';
$string['slotdatetimelabel'] = 'Date and time';
$string['startDiscussion'] = 'Start a discussion';
$string['strftimedatetime'] = '%d %b %Y, %I:%M %p';
$string['strikeout'] = 'Strikeout text and add a comment.';
$string['studentdrawingforbidden'] = 'This PDF Workspace activity does not support drawings for your user role.';
$string['studenttextboxforbidden'] = 'This PDF Workspace activity does not support textboxes for your user role.';
$string['subscribe'] = 'Follow this question';
$string['subscribed'] = 'Subscribed';
$string['subscribedanswers'] = 'to my subscribed questions';
$string['subscribeQuestion'] = 'Subscribe';
$string['subtitleforreportcommentform'] = 'Your message for the course manager';
$string['successfullyEdited'] = 'Changes saved';
$string['successfullyHidden'] = 'Participants now see this comment as hidden.';
$string['successfullymarkedasread'] = 'The report was marked as read.';
$string['successfullymarkedasreadandnolongerdisplayed'] = 'The report was marked as read and removed from the table.';
$string['successfullymarkedasunread'] = 'The report was marked as unread.';
$string['successfullymarkedasunreadandnolongerdisplayed'] = 'The report was marked as unread and removed from the table.';
$string['successfullyRedisplayed'] = 'The comment is visible to participants once more';
$string['successfullySubscribed'] = 'Subscribed to question.';
$string['successfullySubscribednotify'] = 'Your subscription to the question was registered.';
$string['successfullyUnsubscribed'] = 'Your subscribtion was cancelled.';
$string['successfullyUnsubscribedPlural'] = 'Your subscribtion was cancelled. All {$a} answers to the question were removed from this table.';
$string['successfullyUnsubscribedSingular'] = 'Your subscribtion to the question was cancelled and the only answer removed from this table.';
$string['successfullyUnsubscribedTwo'] = 'Your subscribtion was cancelled. Both answers to the question were removed from this table.';
$string['sumPages'] = 'Number of pages';

$string['text'] = 'Add a text in the document.';
$string['titleforreportcommentform'] = 'Report comment';
$string['titleforwardform'] = 'Forward question';
$string['toreport'] = 'Report';

$string['unseenreports'] = 'unread only';
$string['unsolvedquestionstitle'] = 'Unsolved Questions';
$string['unsolvedquestionstitle_help'] = 'All unsolved questions in this course are listed.';
$string['unsubscribe'] = 'Stop following this question';
$string['unsubscribe_notification'] = 'To unsubscribe from notification, please click <a href="{$a}">here</a>.';
$string['unsubscribeQuestion'] = 'Unsubscribe';
$string['unsubscribingDidNotWork'] = 'The subscription could not be cancelled.';
$string['use_studentdrawing'] = "Enable drawing for participants?";
$string['use_studenttextbox'] = "Enable textbox tool for participants?";
$string['useprint'] = "Give participants access to the PDF?";
$string['useprint_comments'] = "Give participants access to the PDF and its comments?";
$string['usecombineddownload'] = 'Allow participants to download the PDF with comments';
$string['retrycomments'] = 'Try again';
$string['use_private_comments'] = "Allow participants to write personal notes?";
$string['use_protected_comments'] = "Allow participants to write private comments?";
$string['useprint_document'] = "Give participants access to the PDF?";
$string['usevotes'] = "Allow users to like comments.";

$string['view'] = 'Document';
$string['votes'] = 'Likes';
$string['voteshelpicon'] = 'Likes';
$string['voteshelpicon_help'] = 'This column tells you how many other people take an interest in the question.';
$string['voteshelpicontwo'] = 'Likes';
$string['voteshelpicontwo_help'] = 'This column tells you how often your posts were <em>liked</em>.';

$string['week'] = 'week';
$string['weeks'] = 'weeks';

$string['year'] = 'year';
$string['years'] = 'years';
$string['yesButton'] = 'Yes';

$string['zoom'] = 'zoom';
$string['zoomin'] = 'zoom in';
$string['zoomout'] = 'zoom out';
$string['annotationaudience'] = 'Visible to';
$string['annotationaudience_teacher'] = 'Participant to teachers';
$string['annotationaudience_private'] = 'Author only';
$string['annotationaudience_targeted'] = 'Teacher to one participant';
$string['annotationaudience_public'] = 'Everyone in this activity';
$string['audiencepermissions'] = 'Audiences for new markings';
$string['annotationfeatures'] = 'Tools and features';
$string['in_activity'] = 'In this activity';
$string['audiencepermissions_help'] = 'Choose which audiences participants and teachers can select. All replies in a conversation keep the marking audience.';
$string['audience_student_private'] = 'Participant: private note';
$string['audience_student_protected'] = 'Participant to teachers';
$string['audience_student_public'] = 'Participant to everyone';
$string['audience_staff_private'] = 'Teacher: private note';
$string['audience_staff_targeted'] = 'Teacher to one participant';
$string['audience_staff_public'] = 'Teacher to everyone';
$string['audiencedefault_student'] = 'Default for participants';
$string['audiencedefault_staff'] = 'Default for teachers';
$string['error:audiencedefault'] = 'The default must be enabled among the allowed audiences.';
$string['error:annotationaudience'] = 'This audience is not allowed for your role in this activity.';
$string['error:audiencenone'] = 'Allow at least one audience for each role.';
$string['annotationrecipient'] = 'Select participant';
$string['annotationrecipient_none'] = 'Choose a participant';
$string['error:annotationrecipient'] = 'Choose an enrolled participant before adding a marking.';
$string['marker'] = 'Text-independent marker (hold Shift for a straight line)';
$string['pen_shift_hint'] = 'Pen (hold Shift for a straight strike-through line)';
$string['noCommentsYet'] = 'No comment yet. You can add one.';
$string['firstCommentButton'] = 'Add comment';
$string['markersize'] = 'Marker width';
$string['undo'] = 'Undo last own marking (up to 20 steps)';
$string['redo'] = 'Redo marking';
$string['historyerror'] = 'This step could not be saved. The history was not changed.';
$string['privacy:metadata:pdfworkspace_annotations:recipientid'] = 'The ID of the participant selected to view a marking.';
$string['documentannotatedimmutable'] = '“{$a}” already has markings and cannot be replaced or removed here.';
$string['pdfworkspace:deleteannotateddocument'] = 'Remove PDFs with markings and discussions';
$string['documentdeleteaction'] = 'Remove PDF with markings';
$string['documentannotatedtooltip'] = 'This document already has markings and comments';
$string['documentdeletewarning'] = 'Saving will remove “{$a}” and all of its markings, questions, replies, comments and attachments. You cannot undo this in the workspace.';
$string['documentdeleteconfirm'] = 'Remove anyway';
$string['documentdeleteunconfirmed'] = 'Select the removal checkbox again and confirm the warning.';
$string['documenttabs'] = 'PDF documents';
$string['workspacedownload'] = 'Download workspace';
$string['workspaceinclude'] = 'In workspace download';
$string['workspaceorder'] = 'Download order';
$string['workspacemove'] = 'Move PDF in download order';
$string['workspacefiles_help'] = 'Select the PDFs for the combined download and set their order. Both download options use this order; PDF tabs stay unchanged. New activities initially include all PDFs. PDFs added later start unchecked. Save new uploads first, then select them here.';
$string['workspacewithoutcomments'] = 'Without notes';
$string['workspacewithcomments'] = 'With markings and comments';
$string['workspaceempty'] = 'No PDFs have been selected for the workspace download.';
$string['error:workspaceorder'] = 'Please enter a valid position in the PDF list.';
$string['pdfworkspace:downloadworkspace'] = 'Download workspace as one PDF';
$string['pdfworkspace:downloadworkspacecomments'] = 'Download workspace with comments as one PDF';
$string['setting_useworkspacedownload'] = 'Download workspace';
$string['useworkspacedownload'] = 'Allow participants to download the workspace without notes';
$string['setting_useworkspacedownload_help'] = 'Participants with the Download workspace as one PDF permission can download the selected original PDFs as a single file. Teachers with this permission can also download without this activity setting. Content already present in the original files is retained.';
$string['setting_useworkspacecomments'] = 'Download workspace with comments';
$string['useworkspacecomments'] = 'Allow participants to download the workspace with markings and comments';
$string['setting_useworkspacecomments_help'] = 'Participants with the Download workspace with comments as one PDF permission can download the selected PDFs with markings and comments they are allowed to see. Teachers with this permission can download without this activity setting. This option is independent of the download without notes.';
$string['privacy:metadata:pdfworkspace_annotations:documentid'] = 'The PDF document to which a marking belongs.';

$string['downloadmenu'] = 'Download';
$string['downloadcurrentpdf'] = 'Current PDF';
$string['downloadwholeworkspace'] = 'Entire workspace as one PDF file';

// Contextual administration help.
$string['adminhelp'] = 'Administration help';
$string['adminhelpmore'] = 'More in the help panel';
$string['adminhelptopics'] = 'Help Topics';
$string['adminhelp_overview'] = 'Site-wide settings';
$string['adminhelp_overview_article'] = '<p>Manage site-wide PDF Workspace settings here. Documents, audiences and permitted download variants are also configured in each activity.</p><p>Check changes in a suitable test activity. Display defaults do not rewrite existing comments or their audiences.</p>';
$string['adminhelp_exports'] = 'Setting up PDF export';
$string['adminhelp_exports_article'] = '<p>Combined PDF exports require a suitable Python environment on the server. Enter the configured interpreter path and follow the plugin\'s installation instructions.</p><p>Availability of each download variant still depends on activity settings and permissions. Use your own sample documents for testing.</p>';

$string['invaliddocument'] = 'The PDF document could not be found or read.';
$string['clicktodownload'] = 'Download the PDF: {$a}';
$string['page-mod-pdfworkspace-x'] = 'Any PDF Workspace activity page';
$string['adminhelp_overview_help'] = 'These settings define site-wide defaults. Review the options in each activity for its audience and allowed tools.';
$string['adminhelp_exports_help'] = 'PDF downloads depend on the activity settings. Combined exports also need the configured Python environment; formulas use the local Moodle TeX renderer.';

$string['activityhelp'] = 'PDF Workspace help';
$string['adminhelp_courseoverview'] = 'PDF activities in this course';
$string['adminhelp_courseoverview_help'] = 'Open a PDF activity from this list. Availability and editing actions follow your course permissions.';
$string['adminhelp_courseoverview_article'] = '<p>This overview lists the PDF Workspace activities available to you in this course, together with their descriptions and recent changes. Select an activity name to open its documents and discussions.</p><p>Editing and visibility controls appear only where your role allows them. Document downloads and annotation tools are configured separately for each activity.</p>';


$string['adminhelp_viewer'] = 'Reading and marking PDFs';
$string['adminhelp_viewer_help'] = 'Use the cursor to navigate, or choose a pin, rectangle, text, pen or marker where your role and the activity allow it.';
$string['adminhelp_viewer_article'] = '<p>Select a document tab, then use zoom and page controls to read the PDF. Fullscreen enlarges the workspace; Escape returns to the page and its previous focus.</p><p>Pins and rectangles can have a discussion. Text, pen and marker tools depend on the activity settings and your permissions. Hold Shift for a straight pen or marker line. Undo and redo cover up to 20 of your own recent drawings and textboxes in this tab.</p>';

$string['adminhelp_discussion'] = 'Comments and audiences';
$string['adminhelp_discussion_help'] = 'Check the audience before posting. Replies retain the audience of their marking; personal notes are visible only to you.';
$string['adminhelp_discussion_article'] = '<p>Click a marking to read or write its discussion. Before sending a comment, check the selected audience. Available audiences follow the activity settings and your role; replies keep the marking audience.</p><p>Use the question filters and search to find discussions. The personal audience is visible only to its author. Teacher audiences include the author and permitted staff. A selected-participant audience is visible only to its author and recipient. A hidden comment is governed by the separate moderation permissions.</p>';

$string['adminhelp_downloads'] = 'Downloading PDFs';
$string['adminhelp_downloads_help'] = 'The download menu lists the variants available for your role and this activity. Workspace downloads use the selected documents and saved export order.';
$string['adminhelp_downloads_article'] = '<p>The download menu offers the original document, comments and combined variants where settings and permissions allow them. Exports include only markings and comments you may see. Content already present in the original PDFs is retained.</p><p>Workspace downloads use the documents and export order saved in the activity settings, independently of the viewer tabs. Combined exports need the configured local Python tools; formulas use the local Moodle TeX renderer.</p>';

$string['adminhelp_moderation'] = 'Moderating discussions';
$string['adminhelp_moderation_help'] = 'Use the report overview and moderation actions according to your role. Hiding and deleting comments have different consequences.';
$string['adminhelp_moderation_article'] = '<p>Reported comments appear in the report overview where your role permits it. Review the discussion before acting. Hiding a comment restricts its display under the moderation policy; deleting removes it through the plugin workflow.</p><p>Course staff may see additional restricted discussions only where their permissions allow it. Personal notes remain personal. Moderation controls do not change the audience of existing threads.</p>';

$string['adminhelp_activityoverview'] = 'Finding discussions in the overview';
$string['adminhelp_activityoverview_help'] = 'Use the overview tabs, status and document filters, then select Apply. Each result opens its marking in the PDF.';
$string['adminhelp_activityoverview_article'] = '<p>Questions, your posts and any other tabs available to your role show discussions you can see. Status filters vary by tab. The document filter appears for activities with several PDFs. The page size controls how many entries appear at once.</p><p>Select Show to apply the filters or Reset to restore the default view. Sortable column headings change the order. Select a document or comment preview to open the matching PDF page and discussion.</p><p>Use the bell in the answers list to follow or unfollow a discussion. You can forward a question when your role and the discussion’s visibility allow it.</p>';
$string['openunansweredquestions'] = 'Open Questions Without a Reply';
$string['noopenunansweredquestions'] = 'There are no open questions without a reply in this view.';
$string['overviewshowquestions'] = 'Show questions';
$string['overviewpdfpagefilter'] = 'PDF Page {$a}';
$string['overviewquestionclusters'] = 'Pages with Several Questions';
$string['overviewquestioncluster'] = '{$a->questions} questions from {$a->authors} people';
$string['nooverviewquestionclusters'] = 'No PDF page has questions from more than one person.';
$string['recentownanswers'] = 'New Replies to My Questions';
$string['recentownanswersperiod'] = 'Replies from other people in the past seven days. The newest reply appears first.';
$string['norecentownanswers'] = 'There are no new replies to your questions in this view.';
$string['adminhelp_questiondashboard'] = 'Reviewing questions in the workspace';
$string['adminhelp_questiondashboard_help'] = 'The card opens unanswered questions. Select a bar to see the questions on that PDF page.';
$string['adminhelp_questiondashboard_article'] = '<p>Open Questions Without a Reply lists the oldest question first. A question with a reply can still be open until someone closes it.</p><p>The bars show up to five pages with questions from at least two people. They include open and closed questions from across the workspace. Private notes and hidden or deleted posts are excluded. Select Reset to clear the page filter.</p>';
$string['adminhelp_recentanswers'] = 'New Replies to My Questions';
$string['adminhelp_recentanswers_help'] = 'Find replies from other people in the past seven days, including replies to questions you do not follow.';
$string['adminhelp_recentanswers_article'] = '<p>The list is sorted by when the reply was posted. Open a reply to jump to its discussion in the PDF. Use the bell to follow or unfollow the question.</p><p>To see older replies, select “all” in the status filter. Select “to my subscribed questions” for replies to other discussions you follow.</p>';
$string['adminhelp_activitysettings'] = 'Configuring this activity';
$string['adminhelp_activitysettings_help'] = 'Choose documents, audiences, tools and downloads here. A PDF with existing markings cannot be replaced or removed.';
$string['adminhelp_activitysettings_article'] = '<p>Add PDFs and save the activity before changing their inclusion and order in workspace exports. Use the row controls to choose the export order; viewer tabs keep their own order.</p><p>Use the checkbox to the left of a PDF to mark it for removal. Site administrators and roles with the “Remove PDFs with markings and discussions” permission can confirm the warning for PDFs with markings. Saving removes these PDFs with their markings and discussions. At least one PDF must remain in the workspace.</p><p>Allowed audiences and the default audience apply to new markings. They do not rewrite existing discussions. Enable the required tools and download variants for participants; permissions also control access.</p>';
$string['overviewfilterstatus'] = 'Status filter';
$string['overviewfilterstatus_help'] = 'Choose the status or subscription view for this tab, then select Apply.';
$string['overviewfilterdocument'] = 'Document filter';
$string['overviewfilterdocument_help'] = 'Limit results to one PDF, then select Apply.';
$string['overviewfiltersize'] = 'Entries per page';
$string['overviewfiltersize_help'] = 'Choose how many results appear on one page, then select Apply.';
$string['audiencedefault_student_help'] = 'Preselect an allowed audience for new participant markings. Existing markings and replies keep their audience.';
$string['audiencedefault_staff_help'] = 'Preselect an allowed audience for new staff markings. Existing markings and replies keep their audience.';
