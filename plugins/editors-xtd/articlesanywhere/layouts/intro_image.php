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
        <label id="data_intro_image_enable-lbl" for="data_intro_image_enable" class="control-label"
               rel="tooltip" title="<?php echo JText::_('AA_INTRO_IMAGE_TAG_DESC'); ?>">
            <?php echo JText::_('AA_INTRO_IMAGE'); ?>
        </label>

        <div class="controls">
            <fieldset id="data_intro_image_enable" class="radio btn-group">
                <input type="radio" id="data_intro_image_enable0" name="data_intro_image_enable"
                       value="0" <?php echo ! $params->data_intro_image_enable ? 'checked="checked"' : ''; ?>>
                <label for="data_intro_image_enable0"><?php echo JText::_('JNO'); ?></label>
                <input type="radio" id="data_intro_image_enable1" name="data_intro_image_enable"
                       value="1" <?php echo $params->data_intro_image_enable ? 'checked="checked"' : ''; ?>>
                <label for="data_intro_image_enable1"><?php echo JText::_('JYES'); ?></label>
            </fieldset>
        </div>
    </div>
</div>
