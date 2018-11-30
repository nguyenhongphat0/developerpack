<?php
/**
 *  @author nguyenhongphat0 <nguyenhongphat28121998@gmail.com>
 *  @copyright 2018 nguyenhongphat0
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
    #tools input[type=text].error {
        background-color: #fca1a2;
    }
    #tools input[type=text].warning {
        background-color: #fab604;
    }
    #tools input[type=text].success {
        background-color: #6cca6e;
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
            <input type="text" id="file" onkeyup="monacoEnter(event)">
            <button onclick="monacoOpen()">Open</button>
            <button onclick="monacoSave()">Save</button>
        </div>
    </div>
    <script>
        var require = { paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.15.6/min/vs' } };
    </script>
    <script type="text/javascript" src="views/js/map.js">
    </script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.15.6/min/vs/loader.js">
    </script>
    <script type="text/javascript">
        require(['vs/editor/editor.main'], function (main) {
            var originalModel = monaco.editor.createModel('');
            var modifiedModel = monaco.editor.createModel('');

            diffEditor = monaco.editor.createDiffEditor(document.getElementById("container"), {
                // You can optionally disable the resizing
                enableSplitViewResizing: true,
                language: 'javascript'
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
        function extension(filename) {
            var re = /(?:\.([^.]+))?$/;
            return re.exec(filename)[1];
        }
        function monacoOpen(setModified = true) {
            file = document.getElementById('file').value;
            developerDispatch({
                action: 'open',
                file
            }).then(data => {
                if (data.status == 200) {
                    document.getElementById('file').className = '';
                    diffEditor.getOriginalEditor().setValue(data.content);
                    if (setModified) {
                        diffEditor.getModifiedEditor().setValue(data.content);                    
                    }
                } else if (data.status == 204) {
                    document.getElementById('file').className = 'warning';
                    diffEditor.getOriginalEditor().setValue('');
                } else {
                    document.getElementById('file').className = 'error';
                    diffEditor.getOriginalEditor().setValue('');
                }
                var l = language[extension(file)];
                console.clear();
                monaco.editor.setModelLanguage(diffEditor.getOriginalEditor().getModel(), l);
                monaco.editor.setModelLanguage(diffEditor.getModifiedEditor().getModel(), l);
                console.log('Available access: ');
                data.ls.forEach(e => console.log(e));
                console.log('Current directory: ', data.pwd);
                console.log('Message: ', data.message);
            });
        }
        function monacoSave() {
            document.getElementById('file').value = file;
            var content = diffEditor.getModifiedEditor().getValue();
            if (content == '') {
                var option = window.prompt(`You are about to save a file with blank content. 
Type "delete" if you want to delete the file, or "save" if you really want to save the file "${file}"?`);
                if (option !== 'save') {
                    if (option === 'delete') {
                        developerDispatch({
                            action: 'delete',
                            file
                        }).then(data => {
                            monacoOpen(false);
                            alert(data.message);
                        });
                    }
                    return;
                }
            }
            var confirm = window.confirm('Are you sure you want to make change to file "' + file + '"?');
            if (!confirm) {
                return;
            }
            developerDispatch({
                action: 'save',
                file,
                content
            }).then(data => {
                if (data.status == 200) {
                    document.getElementById('file').className = 'success';
                    monacoOpen(false);
                    alert(data.message);
                } else {
                    document.getElementById('file').className = 'error';
                    alert(data.message);
                }
            });
        }
        function monacoEnter(e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if(code == 13) {
                monacoOpen();
            }
        }
        file = '';
    </script>
</body>
</html>
