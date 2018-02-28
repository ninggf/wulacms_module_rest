<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

@defined('APPROOT') or header('Page Not Found', true, 404) || die();

$tables ['1.0.0'] [] = "CREATE TABLE `{prefix}rest_app` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `create_time` INT UNSIGNED NOT NULL,
    `create_uid` INT UNSIGNED NOT NULL,
    `update_time` INT UNSIGNED NOT NULL,
    `update_uid` INT UNSIGNED NOT NULL,
    `name` VARCHAR(128) NOT NULL COMMENT '应用名称',
    `appkey` VARCHAR(24) NOT NULL COMMENT 'APP ID.',
    `appsecret` VARCHAR(32) NOT NULL COMMENT '安全码',
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态1启用，0禁用',
    `callback_url` varchar(1024) DEFAULT '' COMMENT '回调URL',
    `note` VARCHAR(256) NULL COMMENT '说明',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UDX_APPKEY` (`appkey` ASC)
)  ENGINE=InnoDB DEFAULT CHARACTER SET={encoding} COMMENT='可通过RESTful接入的应用'";
