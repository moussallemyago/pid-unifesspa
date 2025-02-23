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
<div class="form-horizontal well">
    <div class="control-group" style="margin-bottom: 0;">
        <label id="jform_content_type-lbl" for="jform_content_type" class="hasTip control-label"
               title="<?php echo JText::_('AA_CONTENT_TYPE_DESC'); ?>"><?php echo JText::_('AA_CONTENT_TYPE'); ?></label>

        <div class="controls">
            <fieldset id="content_type" class="radio btn-group">
                <input onchange="form.submit()" type="radio" id="content_type0" name="content_type" value="core" <?php echo ($content_type == 'core') ? 'checked="checked"' : ''; ?>>
                <label for="content_type0" class="btn btn-default"><?php echo JText::_('AA_CONTENT_TYPE_CORE'); ?></label>
                <input onchange="form.submit()" type="radio" id="content_type1" name="content_type" value="k2" <?php echo ($content_type == 'k2') ? 'checked="checked"' : ''; ?>>
                <label for="content_type1" class="btn btn-default"><?php echo JText::_('AA_CONTENT_TYPE_K2'); ?></label>
            </fieldset>
        </div>
    </div>
</div>
