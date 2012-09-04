

<event_calendar>
    <script type='text/javascript'>
        KAJONA.portal.loader.loadFile([
                KAJONA_WEBPATH+"/templates/default/js/fullcalendar/fullcalendar.min.js",
                KAJONA_WEBPATH+"/templates/default/js/fullcalendar/fullcalendar.css"
            ], 
            function() {
                 //files are loaded, initialize the calendar...
                 $('#eventmanagerCalendar').fullCalendar({
                     buttonText: {
                         today: '[lang,cal_today,eventmanager]'
                     },
                     events: "%%cal_eventsource%%",
                     firstDay: 1
            })
        }, true);
    </script>
    <div id="eventmanagerCalendar"></div>
</event_calendar>


<!-- available placeholders: folderlist, filelist, pathnavigation, link_back, link_pages, link_forward -->
<event_list>
    <div>
        <table width="90%">
            %%events%%
        </table>
    </div>
</event_list>

<!-- available placeholders: title, description, location, dateTimeFrom, dateFrom, dateTimeUntil, dateUntil, detailsLinkHref, registerLinkHref -->
<event_list_entry>
    <tr>
        <td>%%dateFrom%%</td>
        <td width="50%">%%title%%</td>
        <td width="20%"><a href="%%detailsLinkHref%%">[lang,detailslink,eventmanager]</a></td>
        <td width="15%"><a href="%%registerLinkHref%%">[lang,registerlink,eventmanager]</a></td>
    </tr>
</event_list_entry>

<!-- available placeholders: title, description, location, dateTimeFrom, dateFrom, dateTimeUntil, dateUntil, registerLink, registerLinkHref, maximumParticipants -->
<event_details>
    <div>
        <h2>%%title%%</h2>
        <p>%%description%%</p>
        <table>
            <tr><td>[lang,location,eventmanager]</td><td>%%location%%</td></tr>
            <tr><td>[lang,dateTimeFrom,eventmanager]</td><td>%%dateTimeFrom%%</td></tr>
            <tr><td>[lang,dateTimeUntil,eventmanager]</td><td>%%dateTimeUntil%%</td></tr>
            <tr><td>[lang,maximumParticipants,eventmanager]</td><td>%%maximumParticipants%%</td></tr>
            <tr><td>[lang,currentParticipants,eventmanager]</td><td>%%currentParticipants%%</td></tr>
        </table>
        %%registerLink%%
    </div>
</event_details>

<!-- available placeholders: title, description, location, dateTimeFrom, dateFrom, dateTimeUntil, dateUntil, registerLinkHref, maximumParticipants -->
<event_details_registerlink>
    <a href="%%registerLinkHref%%">[lang,registerlink,eventmanager]</a>
</event_details_registerlink>

<!-- available placeholders: title, location, dateTimeFrom, dateFrom, dateTimeUntil, dateUntil, formaction -->
<!-- expected form-fields: forename, lastname, email, phone, comment, form_captcha -->
<event_register>
    <div>
        <h2>%%title%%</h2>
        <table>
            <tr><td>[lang,dateTimeFrom,eventmanager]</td><td>%%dateTimeFrom%%</td></tr>
            <tr><td>[lang,dateTimeUntil,eventmanager]</td><td>%%dateTimeUntil%%</td></tr>
        </table>
        <hr />
        
        <form name="formEventRegistration" method="post" action="%%formaction%%" accept-charset="UTF-8" autocomplete="off">
            %%formErrors%%
            <div><label for="forename">[lang,forename,eventmanager]</label><input type="text" name="forename" id="forename" value="%%forename%%" class="inputText" /></div><br />
            <div><label for="lastname">[lang,lastname,eventmanager]</label><input type="text" name="lastname" id="lastname" value="%%lastname%%" class="inputText" /></div><br />
            <div><label for="email">[lang,email,eventmanager]</label><input type="text" name="email" id="email" value="%%email%%" class="inputText" /></div><br />
            <div><label for="phone">[lang,phone,eventmanager]</label><input type="text" name="phone" id="phone" value="%%phone%%" class="inputText" /></div><br />
            <div><label for="comment">[lang,comment,eventmanager]</label><textarea name="comment" id="comment" class="inputTextarea">%%comment%%</textarea></div><br />
            <div><label for="kajonaCaptcha_eventreg"></label><span id="kajonaCaptcha_eventreg"><script type="text/javascript">KAJONA.portal.loadCaptcha('eventreg', 180);</script></span> (<a href="#" onclick="KAJONA.portal.loadCaptcha('eventreg', 180); return false;">[lang,commons_captcha_reload,eventmanager]</a>)</div><br />
            <div><label for="form_captcha">[lang,commons_captcha,eventmanager]</label><input type="text" name="form_captcha" id="form_captcha" class="inputText" autocomplete="off" /></div><br />
            <div><label for="Submit"></label><input type="submit" name="Submit" value="[lang,registerSubmit,eventmanager]" class="button" /></div><br />
            <input type="hidden" name="submitUserRegistration" value="1" />
        </form>

    </div>
</event_register>

<event_register_message>
    <div>
        <h2>%%title%%</h2>
        <p>%%message%%</p>
    </div>
</event_register_message>

<error_row>
    &bull; %%error%%<br />
</error_row>