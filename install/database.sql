-- ========================================
-- 企业福利领取系统 - 数据库结构
-- 适用于 MySQL 5.7+ / MariaDB 10.2+
-- ========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户表（白名单）
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL COMMENT '邮箱（登录凭证）',
  `role` enum('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色：user普通用户，admin管理员',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active' COMMENT '状态',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户白名单表';

-- ----------------------------
-- 福利表
-- ----------------------------
DROP TABLE IF EXISTS `welfare`;
CREATE TABLE `welfare` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '福利名称',
  `amount` decimal(10,2) NOT NULL COMMENT '福利金额',
  `threshold` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '使用门槛',
  `type` varchar(50) NOT NULL COMMENT '分类：car汽车,digital数码,appliance家电,grocery商超,dining餐饮',
  `city` varchar(50) NOT NULL DEFAULT 'national' COMMENT '城市：national全国通用，或具体城市名',
  `brand` varchar(100) DEFAULT NULL COMMENT '品牌',
  `description` text COMMENT '详细说明',
  `daily_limit` int(11) NOT NULL DEFAULT '100' COMMENT '每日限量',
  `probability` int(11) NOT NULL DEFAULT '100' COMMENT '中奖概率0-100',
  `expiry_date` date DEFAULT NULL COMMENT '有效期至',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '状态',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='福利表';

-- ----------------------------
-- 领取记录表
-- ----------------------------
DROP TABLE IF EXISTS `claims`;
CREATE TABLE `claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `welfare_id` int(11) NOT NULL COMMENT '福利ID',
  `success` tinyint(1) NOT NULL COMMENT '是否成功：1成功，0失败',
  `fail_reason` varchar(100) DEFAULT NULL COMMENT '失败原因',
  `claimed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '领取时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_claim` (`user_id`,`welfare_id`),
  KEY `welfare_id` (`welfare_id`),
  CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`welfare_id`) REFERENCES `welfare` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='领取记录表';

-- ----------------------------
-- 每日库存表
-- ----------------------------
DROP TABLE IF EXISTS `daily_stock`;
CREATE TABLE `daily_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `welfare_id` int(11) NOT NULL COMMENT '福利ID',
  `date` date NOT NULL COMMENT '日期',
  `claimed_count` int(11) NOT NULL DEFAULT '0' COMMENT '已领取数量',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily` (`welfare_id`,`date`),
  CONSTRAINT `daily_stock_ibfk_1` FOREIGN KEY (`welfare_id`) REFERENCES `welfare` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日库存表';

-- ----------------------------
-- 初始管理员（安装时会替换）
-- ----------------------------
INSERT INTO `users` (`email`, `role`, `status`) VALUES
('3327512620@qq.com', 'admin', 'active');

-- ----------------------------
-- 初始福利数据
-- ----------------------------
INSERT INTO `welfare` (`title`, `amount`, `threshold`, `type`, `city`, `brand`, `description`, `daily_limit`, `probability`, `expiry_date`, `status`) VALUES
('新能源汽车置换补贴', 12000.00, 150000.00, 'car', 'national', '比亚迪/特斯拉/蔚来', '购买新能源汽车可享受置换补贴，需提供旧车过户证明', 50, 80, '2026-12-31', 'active'),
('燃油车置换补贴', 8000.00, 100000.00, 'car', 'national', '大众/丰田/本田', '购买燃油车可享受置换补贴，需提供旧车过户证明', 30, 70, '2026-12-31', 'active'),
('iPhone 16 Pro Max 补贴', 2000.00, 9999.00, 'digital', 'national', 'Apple', '购买 iPhone 16 Pro Max 立减2000元', 100, 60, '2026-06-30', 'active'),
('华为 Mate 60 RS 补贴', 1800.00, 8999.00, 'digital', 'national', '华为', '购买华为 Mate 60 RS 立减1800元', 100, 65, '2026-06-30', 'active'),
('MacBook Pro M4 补贴', 3000.00, 19999.00, 'digital', 'national', 'Apple', '购买 MacBook Pro M4 立减3000元', 50, 50, '2026-06-30', 'active'),
('戴森全屋清洁套装补贴', 800.00, 5999.00, 'appliance', 'national', '戴森', '购买戴森吸尘器套装立减800元', 80, 75, '2026-06-30', 'active'),
('西门子洗烘套装补贴', 1500.00, 12000.00, 'appliance', 'national', '西门子', '购买西门子洗烘套装立减1500元', 40, 55, '2026-06-30', 'active'),
('海尔卡萨帝冰箱补贴', 1200.00, 8000.00, 'appliance', 'national', '海尔/卡萨帝', '购买海尔或卡萨帝冰箱立减1200元', 60, 70, '2026-06-30', 'active'),
('全家/罗森/711便利店券', 50.00, 0.00, 'grocery', 'national', '全家/罗森/711', '便利店通用消费券，无门槛使用', 500, 90, '2026-03-31', 'active'),
('盒马鲜生年货礼包', 200.00, 500.00, 'grocery', 'national', '盒马', '盒马鲜生满500减200优惠券', 200, 85, '2026-02-28', 'active'),
('海底捞新春聚餐券', 300.00, 600.00, 'dining', 'national', '海底捞', '海底捞满600减300，限堂食使用', 150, 70, '2026-03-15', 'active'),
('星巴克咖啡券', 30.00, 0.00, 'dining', 'national', '星巴克', '星巴克中杯饮品兑换券', 300, 95, '2026-06-30', 'active'),
('北京烤鸭优惠券', 150.00, 400.00, 'dining', 'beijing', '全聚德/大董', '北京地区烤鸭店满400减150', 100, 75, '2026-06-30', 'active'),
('上海本帮菜优惠券', 100.00, 300.00, 'dining', 'shanghai', '老正兴/绿波廊', '上海地区本帮菜馆满300减100', 100, 80, '2026-06-30', 'active'),
('广州早茶优惠券', 80.00, 200.00, 'dining', 'guangzhou', '陶陶居/点都德', '广州地区早茶店满200减80', 120, 85, '2026-06-30', 'active'),
('深圳数码商城券', 500.00, 3000.00, 'digital', 'shenzhen', '华强北', '深圳华强北数码商城满3000减500', 80, 60, '2026-06-30', 'active'),
('杭州西湖景区餐饮券', 60.00, 150.00, 'dining', 'hangzhou', '楼外楼/知味观', '杭州西湖景区餐厅满150减60', 100, 80, '2026-06-30', 'active'),
('成都火锅优惠券', 120.00, 350.00, 'dining', 'chengdu', '大龙燚/蜀大侠', '成都火锅店满350减120', 100, 75, '2026-06-30', 'active');

SET FOREIGN_KEY_CHECKS = 1;