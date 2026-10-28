ALTER TABLE `#__adrianjdownloads_files` ADD COLUMN `require_login` tinyint unsigned NOT NULL DEFAULT 0 AFTER `access`;
UPDATE `#__adrianjdownloads_files` SET `require_login` = 1 WHERE `access_download` <> 1;
ALTER TABLE `#__adrianjdownloads_files` DROP COLUMN `access_download`;
