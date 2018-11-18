<script type="text/javascript">
    function developerDispatch(data) {
        return $.ajax({
            url: '{$root}modules/developerpack/ajax.php',
            method: 'POST',
            data
        });
    }
</script>

<div class="panel">
    <div class="panel-heading">Information</div>
    <div class="form-group">
        <label class="control-label">Prestashop Version</label>
        <p class="help-block">{$version}</p>
    </div>
    <div class="form-group">
        <label class="control-label">PHP Info</label>
        <div class="input-group">
            <div class="btn-group">
                <button class="btn btn-default" onclick="console.log(phpinfo)">Log to console</button>
                <button class="btn btn-default" onclick="console.log(JSON.stringify(phpinfo, true, 4))">Log to console (expanded)</button>
                <button class="btn btn-default" onclick="console.clear()">Clear console</button>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        developerDispatch({
            action: 'phpinfo'
        }).then(res => window.phpinfo = res);
    </script>
</div>

<div class="panel">
    <div class="panel-heading">Download source code</div>
    <div class="form-group" id="zipped-form">
        <label class="control-label">Zipped source code</label>
        <div class="alert alert-danger hidden" id="zipped-danger">
            Don't forget to remove the zipped source code after download or else it will lead to serious security bleach in your system!
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th><span class="title_box active">File</span></th>
                    <th><span class="title_box active">Size</span></th>
                    <th width="100"><span class="title_box active">Action</span></th>
                </tr>
            </thead>
            <tbody id="zipped">
            </tbody>
        </table>
        <div class="alert alert-success hidden" id="zipped-success">
            File deleted successfully
        </div>
        <script type="text/javascript">
            function humanFileSize(bytes, si) {
                var thresh = si ? 1000 : 1024;
                if(Math.abs(bytes) < thresh) {
                    return bytes + ' B';
                }
                var units = si
                    ? ['kB','MB','GB','TB','PB','EB','ZB','YB']
                    : ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
                var u = -1;
                do {
                    bytes /= thresh;
                    ++u;
                } while(Math.abs(bytes) >= thresh && u < units.length - 1);
                return bytes.toFixed(1)+' '+units[u];
            }
            function updateZipped() {
                $('#zipped').html('');
                developerDispatch({
                    action: 'zipped'
                }).then(res => {
                    if (res.length > 0) {
                        $('#zipped-danger').removeClass('hidden');
                        res.forEach(file =>
                            $('<tr>').append(
                                $('<td>').append(
                                    $('<a>', {
                                        href: '{$root}modules/developerpack/zip/' + file.name,
                                        text: file.name
                                    })
                                ),
                                $('<td>' + humanFileSize(file.size) + '</td>'),
                                $('<td>').append(
                                    $('<button />', {
                                        class: 'btn btn-default',
                                        text: 'Delete',
                                        click: function() {
                                            developerDispatch({
                                                action: 'dearchive',
                                                file: file.name
                                            }).then(res => {
                                                $(this).parent().parent().remove();
                                                $('#zipped-success').removeClass('hidden');
                                            });
                                        }
                                    })
                                )
                        ).appendTo('#zipped'))}
                });
            }
            updateZipped();
        </script>
    </div>
    <div class="form-group">
        <label class="control-label">Download source code options</label>
        <div class="input-group">
            <textarea name="options" rows="8" cols="80" id="zip-options">
{
    "action": "zip",
    "output": "site.zip",
    "rule": "include",
    "files": [
        "/override",
        ".json"
    ],
    "maxsize": 1000000
}
            </textarea>
        </div>
        <div class="input-group">
            <div class="btn-group">
                <button class="btn btn-default" onclick="minimalist()">Minimalist</button>
                <button class="btn btn-default" onclick="sourcecode()">Source Code</button>
                <button class="btn btn-default" onclick="full()">Full</button>
                <button class="btn btn-primary" onclick="createZip(this)">Create Zip</button>
            </div>
        </div>
        <div class="alert alert-success hidden" id="created-zip-alert">
            Zip file has been created successfully. Download it now: <a id="created-zip"></a>
        <div>
    </div>
    <script type="text/javascript">
        function updateOptions(options) {
            $('#zip-options').val(JSON.stringify(options, true, 4));
        }
        function minimalist() {
            updateOptions({
                action: "zip",
                output: "minimalist.zip",
                rule: "include",
                files: [
                    "/override",
                    "/themes",
                    "/modules"
                ]
            });
        }
        function sourcecode() {
            updateOptions({
                action: "zip",
                output: "sourcecode.zip",
                rule: "exclude",
                files: [
                    "/vendor",
                    "/var",
                    "/cache",
                    "/img",
                    "/download",
                    "/upload",
                    "/localization",
                    ".zip",
                    ".rar",
                    ".jpg",
                    ".png",
                    ".gif",
                    ".mp3",
                    ".mp4"
                ]
            });
        }
        function full() {
            updateOptions({
                action: "zip",
                output: "full.zip",
                rule: "exclude",
                files: [
                    "/.keep"
                ],
                maxsize: 10000000,
                timeout: 300
            });
        }
        function createZip(self) {
            $(self).attr('disabled', 'disabled');
            let options = JSON.parse($('#zip-options').val());
            developerDispatch(options).then(res => {
                updateZipped();
                $('#created-zip').attr('href', '{$root}modules/developerpack/zip/' + res);
                $('#created-zip').text(res);
                $('#created-zip-alert').removeClass('hidden');
                $(self).removeAttr('disabled')
            }).fail(res => {
                alert("Something went wrong. Open console to view error");
                console.log(res);
                $(self).removeAttr('disabled')
            });
        }
    </script>
</div>