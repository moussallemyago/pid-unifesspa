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
        <label id="data_readmore_enable-lbl" for="data_readmore_enable" class="control-label"
               rel="tooltip" title="<?php echo JText::_('AA_READMORE_TAG_DESC'); ?>">
            <?php echo JText::_('AA_READMORE_LINK'); ?>
        </label>

        <div class="controls">
            <fieldset id="data_readmore_enable" class="radio btn-group">
                <input type="radio" id="data_readmore_enable0" name="data_readmore_enable"
                       value="0" <?php echo ! $params->data_readmore_enable ? 'checked="checked"' : ''; ?>>
                <label for="data_readmore_enable0"><?php echo JText::_('JNO'); ?></label>
                <input type="radio" id="data_readmore_enable1" name="data_readmore_enable"
                       value="1" <?php echo $params->data_readmore_enable ? 'checked="checked"' : ''; ?>>
                <label for="data_readmore_enable1"><?php echo JText::_('JYES'); ?></label>
            </fieldset>
        </div>
    </div>

    <div rel="data_readmore_enable" class="toggle_div" style="display:none;">
        <div class="control-group">
            <label id="data_readmore_text-lbl" for="data_readmore_text" class="control-label"
                   rel="tooltip" title="<?php echo JText::_('AA_READMORE_TEXT_DESC'); ?>">
                <?php echo JText::_('AA_READMORE_TEXT'); ?>
            </label>

            <div class="controls">
                <input type="text" name="data_readmore_text" id="data_readmore_text"
                       value="<?php echo $params->data_readmore_text; ?>">
            </div>
        </div>
        <div class="control-group">
            <label id="data_readmore_class-lbl" for="data_readmore_class" class="control-label"
                   rel="tooltip" title="<?php echo JText::_('AA_CLASSNAME_DESC'); ?>">
                <?php echo JText::_('AA_CLASSNAME'); ?>
            </label>

            <div class="controls">
                <input type="text" name="data_readmore_class" id="data_readmore_class"
                       value="<?php echo $params->data_readmore_class; ?>">
            </div>
        </div>
    </div>
</div>
