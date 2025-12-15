-- HeyGen API Database Schema
-- Version 1.0

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: video_generations
-- ----------------------------
DROP TABLE IF EXISTS `video_generations`;
CREATE TABLE `video_generations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `heygen_video_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'ID returned by HeyGen API',
    `title` VARCHAR(255) NOT NULL,
    `script` TEXT NOT NULL COMMENT 'Text for avatar to speak',
    `avatar_id` VARCHAR(100) NOT NULL,
    `voice_id` VARCHAR(100) NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `video_url` TEXT NULL DEFAULT NULL,
    `thumbnail_url` TEXT NULL DEFAULT NULL,
    `duration` FLOAT NULL DEFAULT NULL COMMENT 'Duration in seconds',
    `width` INT UNSIGNED NOT NULL DEFAULT 1920,
    `height` INT UNSIGNED NOT NULL DEFAULT 1080,
    `background_color` VARCHAR(20) NULL DEFAULT '#FFFFFF',
    `background_image_url` TEXT NULL DEFAULT NULL,
    `speed` FLOAT NOT NULL DEFAULT 1.0 COMMENT 'Speech speed multiplier',
    `error_message` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_heygen_video_id` (`heygen_video_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: avatars_cache
-- ----------------------------
DROP TABLE IF EXISTS `avatars_cache`;
CREATE TABLE `avatars_cache` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `heygen_avatar_id` VARCHAR(100) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `preview_url` TEXT NULL DEFAULT NULL,
    `gender` VARCHAR(20) NULL DEFAULT NULL,
    `avatar_type` VARCHAR(50) NULL DEFAULT NULL COMMENT 'public, private, etc.',
    `cached_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_heygen_avatar_id` (`heygen_avatar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: voices_cache
-- ----------------------------
DROP TABLE IF EXISTS `voices_cache`;
CREATE TABLE `voices_cache` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `heygen_voice_id` VARCHAR(100) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `language` VARCHAR(50) NULL DEFAULT NULL,
    `gender` VARCHAR(20) NULL DEFAULT NULL,
    `preview_url` TEXT NULL DEFAULT NULL,
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `cached_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_heygen_voice_id` (`heygen_voice_id`),
    INDEX `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

