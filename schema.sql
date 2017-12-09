CREATE SCHEMA vk
  DEFAULT CHARACTER SET 'utf8';
USE vk;

CREATE TABLE `order`
(
  id              INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(255)     NOT NULL,
  description     TEXT             NOT NULL,
  creator_user_id INT(11) UNSIGNED NOT NULL,
  done            TINYINT(1)       NOT NULL,
  price           DECIMAL(10, 2)   NOT NULL
);

CREATE TABLE transaction
(
  id       INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT(11) UNSIGNED NOT NULL,
  sum      DECIMAL(10, 2)   NOT NULL,
  finished TINYINT(1)       NOT NULL,
  balance  DECIMAL(10, 2)   NOT NULL,
  user_id  INT(11) UNSIGNED NOT NULL,
  CONSTRAINT transaction_order_id_uindex
  UNIQUE (order_id)
);

CREATE TABLE user
(
  id                INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username          VARCHAR(255)                  NULL,
  password_hash     VARCHAR(255)                  NOT NULL,
  balance           DECIMAL(10, 2) DEFAULT '0.00' NOT NULL,
  can_create_order  TINYINT(1) DEFAULT '0'        NOT NULL,
  can_execute_order TINYINT(1) DEFAULT '0'        NOT NULL,
  CONSTRAINT user_username_uindex
  UNIQUE (username)
);


