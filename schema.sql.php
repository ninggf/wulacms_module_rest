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

$tables ['1.0.0'] [] = "CREATE TABLE IF NOT EXISTS `{prefix}rest_app` (
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

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}app_client` (
    `id` VARCHAR(32) NOT NULL COMMENT 'ID',
    `create_time` INT NOT NULL COMMENT '创建时间',
    `ip` VARCHAR(64) NOT NULL COMMENT 'IP',
    `device` VARCHAR(16) NOT NULL COMMENT '设备',
    PRIMARY KEY (`id`),
    INDEX `IDX_TIME` (`create_time` ASC),
    INDEX `IDX_IP` (`ip` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='APP端'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}app_client_log` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `create_time` INT NOT NULL COMMENT '活跃时间',
    `day` DATE NOT NULL COMMENT '日期',
    `client_id` VARCHAR(32) NOT NULL COMMENT '端ID',
    `device` VARCHAR(16) NOT NULL COMMENT '设备',
    `ver` VARCHAR(24) NOT NULL COMMENT '程序版本',
    `ip` VARCHAR(45) NOT NULL COMMENT 'IP',
    `uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    PRIMARY KEY (`id`),
    INDEX `FK_CID` (`client_id` ASC),
    UNIQUE INDEX `IDX_TIME` (`day` ASC, `device` ASC, `client_id` ASC, `uid` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='活跃记录'";

$tables['1.1.0'][] = "CREATE TABLE IF NOT EXISTS  `{prefix}app_version` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `create_uid` INT(11) UNSIGNED DEFAULT NULL,
    `update_uid` INT(11) UNSIGNED DEFAULT NULL,
    `create_time` INT(11) UNSIGNED DEFAULT NULL,
    `update_time` INT(11) UNSIGNED DEFAULT NULL,
    `deleted` TINYINT(4) UNSIGNED DEFAULT '0' COMMENT '0正常 1删除',
    `appkey` VARCHAR(24) NOT NULL COMMENT '关联rest_app表',
    `update_type` TINYINT(3) UNSIGNED DEFAULT '0' COMMENT '是否强制升级 0 否 1 强制升级',
    `version` VARCHAR(10) NOT NULL COMMENT '版本',
    `vercode` INT(10) UNSIGNED NOT NULL COMMENT '版本号',
    `file` VARCHAR(512) DEFAULT NULL COMMENT '软件包路径',
    `size` INT(10) UNSIGNED DEFAULT '0' COMMENT '软件包体积',
    `prefix` VARCHAR(16) DEFAULT NULL COMMENT '渠道包名前缀',
    `cfgid` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '云控配置',
    `desc` TEXT COMMENT '更新描述',
    PRIMARY KEY (`id`)
)  ENGINE=INNODB DEFAULT CHARSET={encoding} COMMENT='app版本控制'";

$tables['1.1.0'][] = "CREATE TABLE IF NOT EXISTS  `{prefix}app_cfg` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,  
    `pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '继承自配置',
    `name` VARCHAR(32) NOT NULL COMMENT '配置名称',
    `options` LONGTEXT COMMENT '配置选项（jSON格式）',
    PRIMARY KEY (`id`)
)  ENGINE=INNODB DEFAULT CHARSET={encoding} COMMENT='app云控配置'";

//添加渠道字段
$tables['1.1.0'][] = "ALTER TABLE `{prefix}app_client` ADD COLUMN `channel` VARCHAR(16) NULL COMMENT '渠道' AFTER `device`";
//默认配置
$tables['1.1.0'][] = "INSERT INTO `{prefix}app_cfg` (`id`,`pid`,`name`,`options`) VALUES (1,0,'默认配置',NULL)";
//添加平台
$tables['1.1.0'][] = "ALTER TABLE `{prefix}rest_app` ADD COLUMN `platform` VARCHAR(16) NULL COMMENT '平台' AFTER `status`";
