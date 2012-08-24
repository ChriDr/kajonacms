<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Kajona admin [%%webpathTitle%%]</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow" />
    <meta name="generator" content="Kajona, www.kajona.de" />

    <!--<link href="css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet">-->
    <!-- <link rel="stylesheet" href="_skinwebpath_/styles.css?_system_browser_cachebuster_" > -->

    <link href="_skinwebpath_/less/bootstrap.less?_system_browser_cachebuster_" rel="stylesheet/less">
    <link href="_skinwebpath_/less/responsive.less?_system_browser_cachebuster_" rel="stylesheet/less">
    <script> less = { env:'development' }; </script>
    <script src="_skinwebpath_/less/less.js"></script>

    <script src="_webpath_/core/module_system/admin/scripts/jquery/jquery.min.js?_system_browser_cachebuster_"></script>
    <script src="_webpath_/core/module_system/admin/scripts/jqueryui/jquery-ui.custom.min.js?_system_browser_cachebuster_"></script>
    %%head%%
    <script src="_webpath_/core/module_system/admin/scripts/kajona.js?_system_browser_cachebuster_"></script>

    <script>


    </script>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="_skinwebpath_/js/html5.js?_system_browser_cachebuster_"></script>
    <![endif]-->

    <link rel="shortcut icon" href="_skinwebpath_/img/favicon.png">
    <!--
    <link rel="apple-touch-icon" href="_skinwebpath_/img/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="_skinwebpath_/img/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="_skinwebpath_/img/apple-touch-icon-114x114.png">
    -->
</head>

<body>

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span4" style="padding:5px 0 0 10px;">
                    %%login%%
                </div>
                <div class="span8" style="text-align: right;">
                    <form class="navbar-search pull-left">
                        <i id="icon-lupe"></i>
                        <input type="text" class="search-query" placeholder="Suchbegriff" id="globalSearchInput">
                    </form>
                    <select id="languageChooser" class="input-small">
                        <option>English</option>
                        <option>Deutsch</option>
                    </select>

                    %%aspectChooser%%

                    <button id="portaleditor">
                        Portaleditor
                        <i class="icon-share"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span2">&nbsp;</div>
            <div class="span10">
                %%path%%
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row-fluid">

        <!-- MODULE NAVIGATION -->
        <div class="span2">
            <div class="sidebar-nav">
                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <div class="pull-left">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </div>
                    <div class="pull-left">
                        Modules
                    </div>
                </a>
                <div class="nav-collapse" id="moduleNavigation">
                    %%moduleSitemap%%
                </div>
            </div>
        </div>



        <!-- CONTENT CONTAINER -->
        <div class="span10" id="content">

            <div class="contentTopbar clearfix">
                <h1 class="pull-left">%%moduletitle%%</h1>
                <div class="pull-right">%%quickhelp%%</div>
            </div>
            %%content%%


        </div>
    </div>

    <hr>

    <footer>
        <p>powered by <a href="http://www.kajona.de/" target="_blank" title="Kajona - empowering your content">Kajona</a></p>
    </footer>

</div>



<!--<script src="_skinwebpath_/js/jquery-ui-1.8.18.custom.min.js"></script>-->
<script src="_skinwebpath_/js/jquery.ui.touch-punch.min.js"></script>
<script src="_skinwebpath_/js/bootstrap-transition.js"></script>
<script src="_skinwebpath_/js/bootstrap-alert.js"></script>
<script src="_skinwebpath_/js/bootstrap-modal.js"></script>
<script src="_skinwebpath_/js/bootstrap-dropdown.js"></script>
<script src="_skinwebpath_/js/bootstrap-scrollspy.js"></script>
<script src="_skinwebpath_/js/bootstrap-tab.js"></script>
<script src="_skinwebpath_/js/bootstrap-tooltip.js"></script>
<script src="_skinwebpath_/js/bootstrap-popover.js"></script>
<script src="_skinwebpath_/js/bootstrap-button.js"></script>
<script src="_skinwebpath_/js/bootstrap-collapse.js"></script>
<script src="_skinwebpath_/js/bootstrap-carousel.js"></script>
<!--<script src="_skinwebpath_/js/bootstrap-typeahead.js"></script>-->
<!--<script src="_skinwebpath_/js/bootstrap-datepicker.js"></script>-->

<script>

    $(function () {

        $.widget('custom.catcomplete', $.ui.autocomplete, {
            _renderMenu: function(ul, items) {
                var self = this;
                var currentCategory = '';

                $.each(items, function(index, item) {
                    if (item.module != currentCategory) {
                        ul.append('<li class="ui-autocomplete-category"><h3>' + item.module + '</h3></li>');
                        currentCategory = item.module;
                    }
                    self._renderItem(ul, item);
                });

                ul.append('<li class="detailedResults"><a href="#">View detailed search results</a></li>');
                ul.addClass('dropdown-menu');
                ul.addClass('search-dropdown-menu');
            },
            _renderItem: function (ul, item) {
                return $('<li></li>')
                    .data('item.autocomplete', item)
                    .append('<a>' + '<img src="'+item.icon+'" alt="" class="pull-left"><h4 class="pull-left">' + item.systemid + '</h4><br>' + item.description + '</a>')
                    .appendTo(ul);
            }
        });

        $('#globalSearchInput').catcomplete({
            //source: '_skinwebpath_/search.json',
            source: function(request, response) {
                $.ajax({
                    url: KAJONA_WEBPATH+'/xml.php?admin=1',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        query: request.term,
                        module: 'search',
                        action: 'searchXml',
                        asJson: '1'
                    },
                    success: response
                });
            },
            select: function (event, ui) {
                if(ui.item) {
                    document.location = ui.item.link;
                }
            }
        });






        //sidebar responsive
        $('.nav-collapse').on('show', function () {
            var collapsible = $(this);
            window.setTimeout(function () {
                collapsible.css({
                    overflow: 'visible',
                    height: 'auto'
                });
            }, 500);
        });

        $('.nav-collapse').on('hide', function () {
            $(this).css('overflow', '');
        });




        $('#myModal1').on('show', function () {
            var $modal = $(this);
            var $progressbar = $modal.find('.progress > .bar');
            var progress = 0;

            var interval = window.setInterval(function () {
                progress += 10;
                $progressbar.css('width', progress + '%');

                if (progress >= 100) {
                    $modal.modal('hide');

                    window.clearInterval(interval);
                    $progressbar.css('width', '0%');
                }
            }, 1000);

        });


        // insert demo thumbnails
        var $thumb = $('.gallery li').first();
        for (var i = 2; i < 12; i++) {
            var $newThumb = $thumb.clone();
            $newThumb.find('.number').html(i);
            $('.gallery').append($newThumb);
        }

        // init popovers & tooltips
        $('#content a[rel=popover]').popover();

        $('*[rel=tooltip]').tooltip();


        KAJONA.admin.contextMenu.showElementMenu = function() {};

        KAJONA.admin.statusDisplay.classOfMessageBox = "alert alert-info";
        KAJONA.admin.statusDisplay.classOfErrorBox = "alert alert-error";

        KAJONA.admin.scroll = null;
        $(window).scroll(function() {
            var scroll = $(this).scrollTop();
            if(scroll > 10 && KAJONA.admin.scroll != 'top') {
                $("ul.breadcrumb").addClass("breadcrumbTop");
                KAJONA.admin.scroll = "top";
            }
            else if(scroll <= 10 && KAJONA.admin.scroll != 'margin') {
                $("ul.breadcrumb").removeClass("breadcrumbTop");
                KAJONA.admin.scroll = "fixed";
            }


        });
    });

</script>



<div class="modal hide fade fullsize" id="folderviewDialog" role="dialog">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>BROWSER</h3>
    </div>
    <div class="modal-body">
        <div id="folderviewDialog_content"><!-- filled by js --></div>
    </div>
</div>


<script type="text/javascript">
    KAJONA.admin.loader.loadFile("_skinwebpath_/js/kajona_dialog.js", function() {
        KAJONA.admin.folderview.dialog = new KAJONA.admin.ModalDialog('folderviewDialog', 0, true, true);
    }, true);
</script>

<div id="jsStatusBox" class="" style="display: none; position: absolute;"><div class="jsStatusBoxHeader">Status-Info</div><div id="jsStatusBoxContent" class="jsStatusBoxContent"></div></div>

</body>
</html>