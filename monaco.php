<?php
/*
*  @author nguyenhongphat0 <nguyenhongphat28121998@gmail.com>
*  @license https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
*/

// Load required permission before open monaco
include_once('../../config/config.inc.php');
include_once('../../init.php');

$cookie = new Cookie('psAdmin');
$is_admin = $cookie->id_employee;
$is_enabled = Module::isEnabled('developerpack');
if (!$is_admin || !$is_enabled) {
    die('Access dinied');
}

/**
 * Security passed!
 * After the closing PHP tag you will be able to use monaco
 */
?>
<!DOCTYPE html>
<html>
<head>
<title>Monaco</title>
<link rel="icon" type="image/x-icon" href="logo.png" />
<style>
    html, body { margin: 0; overflow: hidden; }
    #container {
        height: calc(100vh - 30px);
    }
    #tools {
        height: 30px;
        width: 100%;
        background-color: #282c34;
        position: fixed;
        bottom: 0px;
    }
    #tools #left {
        float: left;
    }
    #tools #right {
        float: right;
    }
    #tools #left span {
        font-size: 10px;
        color: white;
        line-height: 30px;
        padding-left: 10px;
    }
    #tools #right > * {
        float: left;
    }
    #tools button {
        background-color: #2d89ef;
        color: white;
        padding: 0px 20px;
        height: 30px;
        line-height: 30px;
        border: none;
        cursor: pointer;
    }
    #tools button:hover {
        background-color: #2b5797;
    }
    #tools button:active {
        font-weight: bold;
    }
    #tools input[type=text] {
        font-size: 12px;
        background-color: #21252b;
        border: none;
        color: white;
        height: 30px;
        padding: 0px 10px;
        outline: none;
        width: 50vw;
    }
    #tools input[type=text]:focus {
        background-color: #ddd;
        color: #21252b;
    }
    #tools img {
        height: 20px;
        margin: 5px;
        float: right;
    }
</style>
</head>
<body>
    <div id="container"></div>
    <div id="tools">
        <div id="left">
            <span>Powered by </span>
            <img src="https://opensource.microsoft.com/img/microsoft.png" alt="">
        </div>
        <div id="right">
            <input type="text" id="file">
            <button onclick="monacoOpen()">Open</button>
            <button onclick="monacoSave()">Save</button>
        </div>
    </div>
    <script type="text/javascript" src="vs/loader.js"></script>
    <script type="text/javascript">
        require(['vs/editor/editor.main'], function (main) {
            var originalModel = monaco.editor.createModel("This line is removed on the right.\njust some text\nabcd\nefgh\nSome more text", "text/plain");
            var modifiedModel = monaco.editor.createModel("just some text\nabcz\nzzzzefgh\nSome more text.\nThis line is removed on the left.", "text/plain");

            diffEditor = monaco.editor.createDiffEditor(document.getElementById("container"), {
            	// You can optionally disable the resizing
            	enableSplitViewResizing: false
            });
            diffEditor.setModel({
            	original: originalModel,
            	modified: modifiedModel
            });

            window.addEventListener('resize', function () {
                diffEditor.layout();
            });
        });
        function developerDispatch(data) {
            var body = new FormData();
            for (key in data) {
                body.append(key, data[key]);
            }
            return fetch('<?php echo __PS_BASE_URI__; ?>modules/developerpack/ajax.php', {
                method: 'POST',
                body,
            }).then(res => res.json());
        }
        function monacoOpen() {
            var file = document.getElementById('file').value;
            console.log(file);
            developerDispatch({
                action: 'open',
                file
            }).then(data => {
                diffEditor.getOriginalEditor().setValue(data.content);
                diffEditor.getModifiedEditor().setValue(data.content);
            });
        }
        function monacoSave() {
            var content = diffEditor.getModifiedEditor().getValue();

            console.log(content);
        }
    </script>
</body>
</html>
