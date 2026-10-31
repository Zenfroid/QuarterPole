-- Fix list, October 31.
-- 1. Dominant white is one gene with two copies (W5/W20, W20/n, n/n), not three separate genes.
ALTER TABLE qp_horses ADD COLUMN gene_w VARCHAR(7) NOT NULL DEFAULT 'n/n' AFTER gene_o;
UPDATE qp_horses SET gene_w = CASE
    WHEN gene_w5 = 'Wn' AND gene_w20 <> 'nn' THEN 'W5/W20'
    WHEN gene_w5 = 'Wn'                      THEN 'W5/n'
    WHEN gene_w22 = 'Wn' AND gene_w20 <> 'nn' THEN 'W20/W22'
    WHEN gene_w22 = 'Wn'                     THEN 'W22/n'
    WHEN gene_w20 = 'WW'                     THEN 'W20/W20'
    WHEN gene_w20 = 'Wn'                     THEN 'W20/n'
    ELSE 'n/n' END;
ALTER TABLE qp_horses DROP COLUMN gene_w5, DROP COLUMN gene_w20, DROP COLUMN gene_w22;

-- 2. Each horse holds its peak for 1 to 3 game years.
ALTER TABLE qp_horses ADD COLUMN peak_years TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER peak_age;
UPDATE qp_horses SET peak_years = 1 + FLOOR(RAND() * 3);
UPDATE qp_settings SET setting_value = '1,3',
       description = 'Game years a horse stays at peak: shortest,longest (rolled per horse, foals inherit it)'
 WHERE setting_key = 'peak_years';

-- 3. Track condition is set the night before, once per track and surface.
CREATE TABLE IF NOT EXISTS qp_track_conditions (
  track_id   SMALLINT UNSIGNED NOT NULL,
  run_on     DATE NOT NULL,
  surface    ENUM('dirt','turf','steeplechase') NOT NULL,
  track_condition VARCHAR(16) NOT NULL,
  PRIMARY KEY (track_id, run_on, surface),
  CONSTRAINT fk_tc_track FOREIGN KEY (track_id) REFERENCES qp_tracks(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Bank: players send Cash to each other.
ALTER TABLE qp_transactions MODIFY kind ENUM('start','purse','entry_fee','stud_fee_in','stud_fee_out','sale','purchase',
    'claim_in','claim_out','refund','interest','genetic_test','trainer_report','admin','transfer_in','transfer_out') NOT NULL;
