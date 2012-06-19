<!-- see section "Template-API" of element manual for a list of available placeholders -->

<!-- available placeholders: files -->
<directorybrowser_wrapper>
    <table style="width: 100%">
        <tr>
            <th>%%lang_directorybrowser_name%%</th>
            <th>%%lang_directorybrowser_size%%</th>
            <th>%%lang_directorybrowser_date%%</th>
        </tr>
        %%files%%
    </table>
    
</directorybrowser_wrapper>


<!-- available placeholders: file_size, file_name, file_date, file_href -->
<directorybrowser_entry>
    <tr>
        <td><a href="%%file_href%%">%%file_name%%</a></td>
        <td>%%file_size%%</td>
        <td>%%file_date%%</td>
    </tr>    
</directorybrowser_entry>