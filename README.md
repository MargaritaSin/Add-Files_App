<p align="center">
    <h1 align="center">PHP application with the functionality of adding files</h1>
    <br>
</p>

Data storage cloud with the ability to differentiate file access rights.

User identification via Bearer Token.

<b>DESCRIPTION OF THE GUEST'S FUNCTIONALITY</b>

<b>Authentication:</b><br>
URL: {{host}}/authorization<br>
Method: POST 
Upon successful authentication, the generated user token is returned.

<b>Registration:</b><br>
URL: {{host}}/registration<br>
Method: POST 

Upon successful registration, the generated token of the added user is returned. The function accepts the following parameters:
● email - the user's email is a mandatory, valid e-mail address, unique
● password - the user
's password is mandatory, consists of at least 3 characters, of which at least one is lowercase, one is uppercase and one is a digit
● first_name - required name
● last_name - required last name

<b>DESCRIPTION OF THE AUTHORIZED USER'S FUNCTIONALITY</b>

<b>Authorization reset:</b><br>
URL: {{host}}/logout<br>
Method: GET
The request is intended to clear the value of the user's token.

<b>Uploading files:<b/><br>
URL: {{host}}/files<br>
Method: POST <br>
Headers
- Content-Type: multipart/form-data
  
The function accepts the following parameters:
● files - files, files of no more than 2 MB are allowed, file types: doc, pdf, docx, zip, jpeg, jpg, png;

If any of the files are not uploaded for any reason, the rest should be downloaded and entered into the database.
If the name of the uploaded file matches the name of the file that has already been uploaded by this user, then the uploaded file is renamed to “{NAME} ({I}).{EXT}”. For example: download the file “Juicy chicken.doc ”, and if such a file already exists, then the downloaded file is renamed to “Juicy chicken (1).doc”, or “Juicy chicken (2).doc”, if such a file is already there, etc. Also, each file is associated with its unique identifier file_id - a random string of 10 characters.


<b>File editing:</b><br>
URL: {{host}}/files/<file_id><br>
Method: PATH
The function accepts the following parameters:
● name - the name of the file, not empty, unique to the user

<b>File deletion</b><br>
URL: {{host}}/files/<file_id>

<b>Downloading a file:</b><br>
URL: {{host}}/files/<file_id><br>
Method: GET

<b>Adding access rights:</b><br>
The add file access rights functionality is available only to the file owner. In response, all users who have access to the object are returned.
URL: {{host}}/files/<file_id>/accesses
Method: POST

<b>Removing access rights:</b><br>
The functionality for removing access rights to a file should be available only to the creator. In response, all users who have access to the object should be returned.
URL: {{host}}/files/<file_id>/accesses
Method: DELETE

<b>Viewing user files:</b><br>
URL: {{host}}/files/disk<br>
Method: GET

<b>Viewing files that the user has access to:</b><br>
The user's own files are not present in the list
URL: {{host}}/shared
Method: GET

