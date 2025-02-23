#
#<?php die('Forbidden.'); ?>
#Date: 2025-02-16 04:10:03 UTC
#Software: Joomla Platform 13.1.0 Stable [ Curiosity ] 24-Apr-2013 00:00 GMT

#Fields: datetime	priority clientip	category	message
2025-02-16T04:10:03+00:00	INFO ::1	update	Update started by user Super User (780). Old version is 3.9.6.
2025-02-16T04:10:06+00:00	INFO ::1	update	Downloading update file from https://downloads.joomla.org/cms/joomla3/3-10-12/Joomla_3.10.12-Stable-Update_Package.zip.
2025-02-16T04:10:18+00:00	INFO ::1	update	File Joomla_3.10.12-Stable-Update_Package.zip downloaded.
2025-02-16T04:10:18+00:00	INFO ::1	update	Starting installation of new version.
2025-02-16T04:11:14+00:00	INFO ::1	update	Finalising installation.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.7-2019-04-23. Query text: ALTER TABLE `#__session` ADD INDEX `client_id_guest` (`client_id`, `guest`);.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.7-2019-04-26. Query text: UPDATE `#__content_types` SET `content_history_options` = REPLACE(`content_histo.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.8-2019-06-11. Query text: UPDATE #__users SET params = REPLACE(params, '",,"', '","');.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.8-2019-06-15. Query text: ALTER TABLE `#__template_styles` DROP INDEX `idx_home`;.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.8-2019-06-15. Query text: ALTER TABLE `#__template_styles` ADD INDEX `idx_client_id` (`client_id`);.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.8-2019-06-15. Query text: ALTER TABLE `#__template_styles` ADD INDEX `idx_client_id_home` (`client_id`, `h.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.10-2019-07-09. Query text: ALTER TABLE `#__template_styles` MODIFY `home` char(7) NOT NULL DEFAULT '0';.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__categories` MODIFY `description` mediumtext;.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__categories` MODIFY `params` text;.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__fields` MODIFY `default_value` text;.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__fields_values` MODIFY `value` text;.
2025-02-16T04:11:14+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__finder_links` MODIFY `description` text;.
2025-02-16T04:11:15+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__modules` MODIFY `content` text;.
2025-02-16T04:11:15+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_body` mediumtext;.
2025-02-16T04:11:15+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_params` text;.
2025-02-16T04:11:15+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_images` text;.
2025-02-16T04:11:15+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_urls` text;.
2025-02-16T04:11:15+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_metakey` text;.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-02-15. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_metadesc` text;.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-03-04. Query text: ALTER TABLE `#__users` DROP INDEX `username`;.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.16-2020-03-04. Query text: ALTER TABLE `#__users` ADD UNIQUE INDEX `idx_username` (`username`);.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.19-2020-05-16. Query text: ALTER TABLE `#__ucm_content` MODIFY `core_title` varchar(400) NOT NULL DEFAULT '.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.19-2020-06-01. Query text: INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.21-2020-08-02. Query text: INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.22-2020-09-16. Query text: INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.26-2021-04-07. Query text: INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.9.27-2021-04-20. Query text: INSERT INTO `#__postinstall_messages` (`extension_id`, `title_key`, `description.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.10.0-2020-08-10. Query text: ALTER TABLE `#__template_styles` ADD COLUMN `inheritable` tinyint NOT NULL DEFAU.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.10.0-2020-08-10. Query text: ALTER TABLE `#__template_styles` ADD COLUMN `parent` varchar(50) DEFAULT '';.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.10.0-2021-05-28. Query text: INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, .
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.10.7-2022-02-20. Query text: DELETE FROM `#__postinstall_messages` WHERE `title_key` = 'COM_ADMIN_POSTINSTALL.
2025-02-16T04:11:16+00:00	INFO ::1	update	Ran query from file 3.10.7-2022-03-18. Query text: ALTER TABLE `#__users` ADD COLUMN `authProvider` VARCHAR(100) NOT NULL DEFAULT '.
2025-02-16T04:11:16+00:00	INFO ::1	update	Deleting removed files and folders.
2025-02-16T04:11:18+00:00	INFO ::1	update	Cleaning up after installation.
2025-02-16T04:11:18+00:00	INFO ::1	update	Update to version 3.10.12 is complete.
