<?php
/**
 * @package         Articles Anywhere
 * @version         14.2.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text as JText;

?>
<div class="well">
    <div class="control-group">
        <label id="data_title_enable-lbl" for="data_title_enable" class="control-label"
               rel="tooltip" title="<?php echo JText::_('AA_TITLE_TAG_DESC'); ?>">
            <?php echo JText::_('JGLOBAL_TITLE'); ?>
        </label>

        <div class="controls">
            <fieldset id="data_title_enable" class="radio btn-group">
                <input type="radio" id="data_title_enable0" name="data_title_enable"
                       value="0" <?php echo ! $params->data_title_enable ? 'checked="checked"' : ''; ?>>
                <label for="data_title_enable0"><?php echo JText::_('JNO'); ?></label>
                <input type="radio" id="data_title_enable1" name="data_title_enable"
                       value="1" <?php echo $params->data_title_enable ? 'checked="checked"' : ''; ?>>
                <label for="data_title_enable1"><?php echo JText::_('JYES'); ?></label>
            </fieldset>
        </div>
    </div>

    <div rel="data_title_enable" class="toggle_div" style="display:none;">
        <div class="control-group">
            <label id="data_title_heading-lbl" for="data_title_heading" class="control-label" rel="tooltip"
                   title="<?php echo JText::_('AA_TITLE_HEADING_DESC'); ?>">
                <?php echo JText::_('AA_TITLE_HEADING'); ?>
            </label>

            <div class="controls">
                <select name="data_title_heading">
                    <option value=""<?php echo ! $params->data_title_heading ? 'selected="selected"' : ''; ?>>
                        <?php echo JText::_('JNONE'); ?>
                    </option>
                    <?php for ($i = 1; $i <= 6; $i++) : ?>
                        <option value="<?php echo 'h' . $i; ?>"<?php echo $params->data_title_heading == 'h' . $i ? 'selected="selected"' : ''; ?>>
                            <?php echo JText::_('RL_HEADING_' . $i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="control-group">
            <label id="data_title_enable-lbl" for="data_title_enable" class="control-label"
                   rel="tooltip" title="<?php echo JText::_('AA_TITLE_ADD_LINK_TAG_DESC'); ?>">
                <?php echo JText::_('AA_ADD_LINK_TAG'); ?>
            </label>

            <div class="controls">
                <fieldset id="data_title_add_link" class="radio btn-group">
                    <input type="radio" id="data_title_add_link0" name="data_title_add_link"
                           value="0" <?php echo ! $params->data_title_add_link ? 'checked="checked"' : ''; ?>>
                    <label for="data_title_add_link0"><?php echo JText::_('JNO'); ?></label>
                    <input type="radio" id="data_title_add_link1" name="data_title_add_link"
                           value="1" <?php echo $params->data_title_add_link ? 'checked="checked"' : ''; ?>>
                    <label for="data_title_add_link1"><?php echo JText::_('JYES'); ?></label>
                </fieldset>
            </div>
        </div>
    </div>
</div>
