<!-- see section "Template-API" of module manual for a list of available placeholders -->

<!-- available placeholders: systemid, folderlist, filelist, pathnavigation, link_back, link_pages, link_forward -->
<list>
    <script type="text/javascript">
        if (YAHOO.lang.isUndefined(arrViewers)) {
            var arrViewers = new Array();

            YAHOO.util.Event.onDOMReady(function () {
                YAHOO.namespace("YAHOO.photoViewer");
                YAHOO.photoViewer.config = { viewers: {} };

                //init all viewers
                for (var i=0; i<arrViewers.length; i++) {
                    YAHOO.photoViewer.config.viewers[arrViewers[i]] = {
                        properties: {
                            id: arrViewers[i],
                            grow: 0.2,
                            fade: 0.2,
                            modal: true,
                            dragable: false,
                            fixedcenter: true,
                            loadFrom: "html",
                            position: "absolute",
                            buttonText: {
                                next: " ",
                                prev: " ",
                                close: "X"
                            },
                            /* remove/rename the slideShow property to disable slideshow feature */
                            slideShow: {
                            	autoStart: false,
                            	duration: 3500,
                            	controlsText: {
                            	    play: " ",
                            	    pause: " ",
                            	    stop: " ",
                            	    display: "{0}/{1}"
	                            }
                            }
                        }
                    };
                }
            });

            KAJONA.portal.loader.load(
                ["dragdrop", "animation", "container"],
            	[KAJONA_WEBPATH+"/templates/default/js/photoviewer/build/photoviewer_base.js",
            	 KAJONA_WEBPATH+"/templates/default/js/photoviewer/assets/skins/kajona/kajona.css"]
            );
        }

        //add viewer
        arrViewers.push("pv_%%systemid%%");
    </script>

    <p>%%pathnavigation%%</p>
    %%folderlist%%
    <div id="pv_%%systemid%%">%%filelist%%</div>
    <p align="center">%%link_back%% %%link_pages%% %%link_forward%%</p>
</list>

<!-- available placeholders: folder_name, folder_description, folder_subtitle, folder_href, folder_preview_image_src -->
<folderlist>
    <table cellspacing="0" class="portalList">
        <tr class="portalListRow1">
            <td class="image"><img src="_webpath_/templates/default/pics/kajona/icon_folderClosed.gif" /></td>
            <td class="title"><a href="%%folder_href%%">%%folder_name%%</a></td>
        </tr>
        <tr class="portalListRow2">
            <td><img src="_webpath_/image.php?image=%%folder_preview_image_src%%&amp;maxWidth=50&amp;maxHeight=50" /></td>
            <td class="description">%%folder_description%%</td>
        </tr>
    </table>
</folderlist>

<!-- the following section is used to wrap a list of files, e.g. in order to build a table.
     If you'd like to have a behaviour like rendering an unlimited list of files per row, use s.th.
     like < filelist >%%file_0%%</ filelist > -->
<!-- available placeholders: file_(nr) -->
<filelist>
    <table width="100%" cellspacing="0">
        <tr>
            <td colspan="3">&nbsp;</td>
        </tr>
        <tr style="text-align: center;">
            <td width="33%">%%file_0%%</td>
            <td width="33%">%%file_1%%</td>
            <td width="33%">%%file_2%%</td>
        </tr>
    </table>
</filelist>



<!-- represents a single file within a filelist
     available placeholders: image_detail_src, file_name, file_filename, file_subtitle, file_description, file_size, file_hits, file_details_href,
     file_owner, file_lmtime, file_link, file_link_href
-->
<filelist_file>
    <div style="text-align: center;">
        <div><a href="%%image_detail_src%%" title="%%name%%" class="photoViewer"><img src="_webpath_/image.php?image=%%file_filename%%&amp;maxWidth=100&amp;maxHeight=100" alt="%%file_subtitle%%" /></a></div>
        <div>%%file_name%%</div>
    </div>
</filelist_file>


<!-- available placeholders:
   image_src, overview, pathnavigation, backlink, forwardlink, backlink_(1..3), forwardlink_(1..3), filestrip_current
   file_systemid, file_name, file_description, file_subtitle, file_filename, file_size, file_hits, file_rating (if module rating installed),
   file_owner, file_lmtime, file_link, file_link_href
-->
<filedetail>
    <!-- not used for imagelightbox -->
</filedetail>


<!-- available placeholder:
    file_name, file_system, file_detail_href
-->
<filedetail_strip>
    <!-- not used for imagelightbox -->
</filedetail_strip>

<!-- available placeholders: pathnavigation_point -->
<pathnavigation_level>
%%pathnavigation_point%% >
</pathnavigation_level>





<!-- available placeholders: rating_icons, rating_bar_title, rating_rating, rating_hits, rating_hits, rating_ratingPercent, system_id -->
<rating_bar>
    <script type="text/javascript">
	    if (typeof bitKajonaRatingsLoaded == "undefined") {
	    	KAJONA.portal.loader.loadAjaxBase(null, "rating.js");
	        var bitKajonaRatingsLoaded = true;
	    }
    </script>
    <span class="inline-rating-bar">
    <ul class="rating-icon" id="kajona_rating_%%system_id%%" onmouseover="KAJONA.portal.tooltip.add(this, '%%rating_bar_title%%');">
        <li class="current-rating" style="width:%%rating_ratingPercent%%%;"></li>
        %%rating_icons%%
    </ul></span> <span id="kajona_rating_rating_%%system_id%%">%%rating_rating%%</span> (<span id="kajona_rating_hits_%%system_id%%">%%rating_hits%%</span>)
</rating_bar>

<!-- available placeholders: rating_icon_number, rating_icon_onclick, rating_icon_title -->
<rating_icon><li><a href="#" onclick="%%rating_icon_onclick%%" onmouseover="KAJONA.portal.tooltip.add(this, '%%rating_icon_title%%');" class="icon-%%rating_icon_number%%">%%rating_icon_number%%</a></li></rating_icon>