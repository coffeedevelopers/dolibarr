<?php /* Smarty version 2.6.26, created on 2018-12-11 14:20:36
         compiled from critbody_prepare.tpl */ ?>
	<TABLE class="swPrpCritBox" cellpadding="0">
<!---->
		<TR id="swPrpCriteriaBody">
			<TD class="swPrpCritEntry">
			<div id="swPrpSubmitPane">
<?php if (! $this->_tpl_vars['IS_ADMIN_SCREEN']): ?>
<?php if ($this->_tpl_vars['SHOW_HIDE_PREPARE_GO_BUTTONS'] == 'show'): ?>
    				<input type="submit" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_GO_BUTTON']; ?>
prepareAjaxExecute swHTMLGoBox" id="prepareAjaxExecute" name="submitPrepare" value="<?php echo $this->_tpl_vars['T_GO']; ?>
">
<?php endif; ?>
<?php if ($this->_tpl_vars['SHOW_HIDE_PREPARE_RESET_BUTTONS'] == 'show'): ?>
    				<input type="submit" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_RESET_BUTTON']; ?>
reporticoSubmit" name="clearform" value="<?php echo $this->_tpl_vars['T_RESET']; ?>
">
<?php endif; ?>
<?php else: ?>
    				<input type="submit" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_GO_BUTTON']; ?>
prepareAjaxExecute swHTMLGoBox" id="prepareAjaxExecute" name="submitPrepare" value="<?php echo $this->_tpl_vars['T_GO']; ?>
">
<?php endif; ?>
<?php if ($this->_tpl_vars['SHOW_MINIMAINTAIN']): ?> 
<div style="float: left">
<?php if (! $this->_tpl_vars['REPORTICO_BOOTSTRAP_MODAL']): ?>
    				<input type="submit" class="prepareMiniMaintain swMiniMaintain" style="margin-right: 30px" title="<?php echo $this->_tpl_vars['T_EDIT']; ?>
 <?php echo $this->_tpl_vars['T_EDITCRITERIA']; ?>
" id="submit_mainquercrit" value="<?php echo $this->_tpl_vars['T_EDITCRITERIA']; ?>
" name="mainquercrit_ANY">
<?php else: ?>
    				<input type="submit" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_TOOLBAR_BUTTON']; ?>
prepareMiniMaintain swMiniMaintain" style="margin-right: 30px" title="<?php echo $this->_tpl_vars['T_EDIT']; ?>
 <?php echo $this->_tpl_vars['T_EDITCRITERIA']; ?>
" id="submit_mainquercrit" value="<?php echo $this->_tpl_vars['T_EDITCRITERIA']; ?>
" name="mainquercrit_ANY">
<?php endif; ?>
</div>
<?php endif; ?>
                    &nbsp;
			</div>

                <TABLE class="swPrpCritEntryBox">
<?php 
$loopct = 0;
 ?>
<?php if (isset ( $this->_tpl_vars['CRITERIA_ITEMS'] )): ?>
<?php unset($this->_sections['critno']);
$this->_sections['critno']['name'] = 'critno';
$this->_sections['critno']['loop'] = is_array($_loop=$this->_tpl_vars['CRITERIA_ITEMS']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['critno']['show'] = true;
$this->_sections['critno']['max'] = $this->_sections['critno']['loop'];
$this->_sections['critno']['step'] = 1;
$this->_sections['critno']['start'] = $this->_sections['critno']['step'] > 0 ? 0 : $this->_sections['critno']['loop']-1;
if ($this->_sections['critno']['show']) {
    $this->_sections['critno']['total'] = $this->_sections['critno']['loop'];
    if ($this->_sections['critno']['total'] == 0)
        $this->_sections['critno']['show'] = false;
} else
    $this->_sections['critno']['total'] = 0;
if ($this->_sections['critno']['show']):

            for ($this->_sections['critno']['index'] = $this->_sections['critno']['start'], $this->_sections['critno']['iteration'] = 1;
                 $this->_sections['critno']['iteration'] <= $this->_sections['critno']['total'];
                 $this->_sections['critno']['index'] += $this->_sections['critno']['step'], $this->_sections['critno']['iteration']++):
$this->_sections['critno']['rownum'] = $this->_sections['critno']['iteration'];
$this->_sections['critno']['index_prev'] = $this->_sections['critno']['index'] - $this->_sections['critno']['step'];
$this->_sections['critno']['index_next'] = $this->_sections['critno']['index'] + $this->_sections['critno']['step'];
$this->_sections['critno']['first']      = ($this->_sections['critno']['iteration'] == 1);
$this->_sections['critno']['last']       = ($this->_sections['critno']['iteration'] == $this->_sections['critno']['total']);
?>
<?php if ($this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group'] && ( $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group'] != $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['last_display_group'] )): ?>
<tr id="swToggleCriteriaDiv<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group_class']; ?>
">
<td colspan="3">
<a class="swToggleCriteria" id="swToggleCriteria<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group_class']; ?>
" href="javascript:toggleCriteria('<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group_class']; ?>
')">+</a>
<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group']; ?>

</td>
</tr>
<?php endif; ?>
<?php if ($this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['hidden'] || $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group']): ?>
<?php if ($this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group']): ?>
                    <tr class="swPrpCritLine  swDisplayGroupLine displayGroup<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['display_group_class']; ?>
" id="criteria_<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['name']; ?>
" style="display:none">
<?php else: ?>
                    <tr class="swPrpCritLine" id="criteria_<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['name']; ?>
" style="display:none">
<?php endif; ?>
<?php else: ?>
                    <tr class="swPrpCritLine" id="criteria_<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['name']; ?>
">
<?php endif; ?>
                        <td class='swPrpCritTitle'>
<?php if ($this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['tooltip']): ?>
<?php if ($this->_tpl_vars['BOOTSTRAP_STYLES']): ?>
<?php if ($this->_tpl_vars['BOOTSTRAP_STYLES'] == '3' || $this->_tpl_vars['BOOTSTRAP_STYLES'] == 'joomla3'): ?>
                            <a class='reportico_tooltip' data-toggle="tooltip" data-placement="right" title="<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['tooltip']; ?>
">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                            </a>
<?php else: ?>
                            <a class='reportico_tooltip' data-toggle="tooltip" data-placement="right" title="<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['tooltip']; ?>
">
                                    <span class="icon-question-sign"></span>
                            </a>
<?php endif; ?>
<?php else: ?>
                            <div class="swHelpIcon" alt="tab" title = "<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['tooltip']; ?>
"><img class="swHelpIcon"></img></div>
<?php endif; ?>
<?php endif; ?>
<?php 
$itemval = str_pad($loopct, 4, '0', STR_PAD_LEFT);
$this->assign('criterianumber', $itemval);
$loopct++;
 ?>
                            <?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['title']; ?>

                        </td>
                        <td class="swPrpCritSel">
                            <?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['entry']; ?>

                        </td>
                        <td class="swPrpCritExpandSel">
<?php if ($this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['expand']): ?>
<?php if ($this->_tpl_vars['AJAX_ENABLED']): ?> 
                            <input class="swPrpCritExpandButton" id="reporticoPerformExpand" type="button" name="EXPAND_<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['name']; ?>
" value="<?php echo $this->_tpl_vars['T_EXPAND']; ?>
">
<?php else: ?>
                            <input class="swPrpCritExpandButton" type="submit" name="EXPAND_<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['name']; ?>
" value="<?php echo $this->_tpl_vars['T_EXPAND']; ?>
">
<?php endif; ?>
<?php endif; ?>
                        </td>
                    </TR>
<?php endfor; endif; ?>
<?php endif; ?>
                </TABLE>
<?php if (isset ( $this->_tpl_vars['CRITERIA_ITEMS'] )): ?>
<?php if (count ( $this->_tpl_vars['CRITERIA_ITEMS'] ) > 1): ?>
<div id="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_SMALL_BUTTON']; ?>
swPrpSubmitPane">
<?php if (! $this->_tpl_vars['IS_ADMIN_SCREEN']): ?>
	<input type="submit" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_GO_BUTTON']; ?>
prepareAjaxExecute swHTMLGoBox" id="prepareAjaxExecute" name="submitPrepare" value="<?php echo $this->_tpl_vars['T_GO']; ?>
">
<?php endif; ?>
    <!--input type="submit" class="reporticoSubmit" name="clearform" value="<?php echo $this->_tpl_vars['T_RESET']; ?>
"-->
</div>
<?php endif; ?>
<?php endif; ?>
			</td>
			<TD class="swPrpExpand">
				<TABLE class="swPrpExpandBox">
					<TR class="swPrpExpandRow">
						<TD id="swPrpExpandCell" rowspan="0" valign="top">
<?php if (strlen ( $this->_tpl_vars['ERRORMSG'] ) > 0): ?>
            <TABLE class="swError">
                <TR>
                    <TD><?php echo $this->_tpl_vars['ERRORMSG']; ?>
</TD>
                </TR>
            </TABLE>
<?php endif; ?>
<?php if (strlen ( $this->_tpl_vars['STATUSMSG'] ) > 0): ?> 
			<TABLE class="swStatus">
				<TR>
					<TD><?php echo $this->_tpl_vars['STATUSMSG']; ?>
</TD>
				</TR>
			</TABLE>
<?php endif; ?>
<?php if (strlen ( $this->_tpl_vars['STATUSMSG'] ) == 0 && strlen ( $this->_tpl_vars['ERRORMSG'] ) == 0): ?>
<div style="float:right; ">
<?php if (strlen ( $this->_tpl_vars['MAIN_MENU_URL'] ) > 0): ?>
<!--a class="swLinkMenu" style="float:left;" href="<?php echo $this->_tpl_vars['MAIN_MENU_URL']; ?>
">&lt;&lt; Menu</a-->
<?php endif; ?>
</div>
<p>
<?php if ($this->_tpl_vars['SHOW_EXPANDED']): ?>
							<?php echo $this->_tpl_vars['T_SEARCH']; ?>
 <?php echo $this->_tpl_vars['EXPANDED_TITLE']; ?>
 :<br><input  id="expandsearch" type="text" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_TEXTFIELD']; ?>
" name="expand_value" style="width: 50%;display: inline" size="30" value="<?php echo $this->_tpl_vars['EXPANDED_SEARCH_VALUE']; ?>
"</input>
									<input id="reporticoSearchExpand" class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_SMALL_BUTTON']; ?>
swPrpSubmit" style="margin-bottom: 2px" type="submit" name="EXPANDSEARCH_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="Search"><br>

<?php echo $this->_tpl_vars['CONTENT']; ?>

							<br>
							<input class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_SMALL_BUTTON']; ?>
swPrpSubmit" type="submit" name="EXPANDCLEAR_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="Clear">
							<input class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_SMALL_BUTTON']; ?>
swPrpSubmit" type="submit" name="EXPANDSELECTALL_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="Select All">
							<input class="<?php echo $this->_tpl_vars['BOOTSTRAP_STYLE_SMALL_BUTTON']; ?>
swPrpSubmit" type="submit" name="EXPANDOK_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="OK">
<?php endif; ?>
<?php if (! $this->_tpl_vars['SHOW_EXPANDED']): ?>
<?php if (! $this->_tpl_vars['REPORT_DESCRIPTION']): ?>
<?php echo $this->_tpl_vars['T_DEFAULT_REPORT_DESCRIPTION']; ?>

<?php else: ?>
						&nbsp;<br>
						<?php echo $this->_tpl_vars['REPORT_DESCRIPTION']; ?>

<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
						</TD>
					</TR>
				</TABLE>
			</TD>
		</TR>
			</TABLE>
