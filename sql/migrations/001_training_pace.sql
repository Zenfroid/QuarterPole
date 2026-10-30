-- For databases created before this change: training progress takes fractions
-- and each Train now adds 0.25 points (400 sessions = full peak).
ALTER TABLE qp_horses MODIFY training_progress DECIMAL(5,2) NOT NULL DEFAULT 0;
INSERT INTO qp_settings (setting_key, setting_value, description)
VALUES ('training_progress_per_session', '0.25', 'Training progress points per Train; 100 = full peak')
ON DUPLICATE KEY UPDATE setting_value = '0.25';
