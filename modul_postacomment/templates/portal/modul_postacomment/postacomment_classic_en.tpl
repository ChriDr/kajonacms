<postacomment_list>
%%postacomment_form%%
%%postacomment_list%%
</postacomment_list>



<postacomment_post>
	<div class="pacComment">
		<div class="pacHeader">
			<div class="pacName">%%postacomment_post_name%%</div>
			<div class="pacDate">%%postacomment_post_date%%</div>
			<div class="pacSubject">%%postacomment_post_subject%%</div>
		</div>	
		<div class="pacText">%%postacomment_post_message%%</div>
	</div>
</postacomment_post>



<postacomment_form>
	<div><a href="javascript:toggle('postaCommentForm');">Write a comment</a></div>
	<div id="postaCommentForm" style="display: none;">
		<form name="formComment" method="post" action="%%formaction%%" accept-charset="UTF-8">
			%%validation_errors%%
			<div><label for="comment_name">Name*:</label><input type="text" name="comment_name" id="comment_name" value="%%comment_name%%" class="inputText" /></div><br />
			<div><label for="comment_subject">Subject:</label><input type="text" name="comment_subject" id="comment_subject" value="%%comment_subject%%" class="inputText" /></div><br />
			<div><label for="comment_message">Message*:</label><textarea name="comment_message" id="comment_message" class="inputTextareaLarge">%%comment_message%%</textarea></div><br /><br />
			<div><label for="kajonaCaptcha"></label><img id="kajonaCaptcha" src="_webpath_/image.php?image=kajonaCaptcha&amp;maxWidth=180" /></div><br />
			<div><label for="form_captcha">Code*:</label><input type="text" name="form_captcha" id="form_captcha" class="inputText" /></div><br />
			<div><label for="Reload"></label><input type="button" name="Reload" onclick="reloadCaptcha('kajonaCaptcha')" value="New Code" class="button" /></div><br /><br />
			<div><label for="Submit"></label><input type="submit" name="Submit" value="Submit" class="button" /></div><br />
		</form>
	</div>
</postacomment_form>

<validation_error_row>
	&middot; %%error%% <br />
</validation_error_row>