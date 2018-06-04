<!DOCTYPE html> <html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>HAR Viewer Test</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head> <body class="harBody"> <div id="content" version="test"></div>
<script src="/harviewer/scripts/jquery.js"></script>
<script data-main="/harviewer/scripts/harViewer" src="/harviewer/scripts/require.js"></script>

<script>
    $("#content").bind("onViewerPreInit", function(event) {
        var viewer = event.target.repObject;
        viewer.removeTab("Home");
//        viewer.removeTab("DOM");
        viewer.removeTab("DOM");
        viewer.removeTab("Home");
        viewer.removeTab("About");
        viewer.removeTab("Schema");

        var preview = viewer.getTab("Preview");
        preview.toolbar.removeButton("download");
        preview.toolbar.removeButton("clear");
        preview.showStats(false);
        preview.showTimeline(false);
    });

    $("#content").bind("onViewerInit", function(event) {
        var viewer = event.target.repObject;
        var log = {!! $log !!};
        viewer.appendPreview(log);
    });

</script>
<link rel="stylesheet" href="/harviewer/css/harViewer.css" type="text/css"/>
</body>
</html>


