-- For databases created before racing: claims, entry fees paid, a refund ledger kind and the racing settings.
ALTER TABLE qp_transactions MODIFY kind ENUM('start','purse','entry_fee','stud_fee_in','stud_fee_out','sale','purchase',
  'claim_in','claim_out','refund','interest','genetic_test','trainer_report','admin') NOT NULL;

ALTER TABLE qp_entries ADD COLUMN fee_paid INT UNSIGNED NOT NULL DEFAULT 0 AFTER earnings;

CREATE TABLE qp_claims (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entry_id    INT UNSIGNED NOT NULL,
  member_id   INT UNSIGNED NOT NULL,
  amount      INT UNSIGNED NOT NULL,
  status      ENUM('pending','won','lost','refunded') NOT NULL DEFAULT 'pending',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_claim (entry_id, member_id),
  CONSTRAINT fk_claim_entry  FOREIGN KEY (entry_id)  REFERENCES qp_entries(id),
  CONSTRAINT fk_claim_member FOREIGN KEY (member_id) REFERENCES qp_stables(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO qp_settings (setting_key, setting_value, description) VALUES
('race_noise_pct',            '2',       'Engine: random speed swing per 1/16 mile (1 sd, %)'),
('race_form_pct',             '2',       'Engine: random form per race (1 sd, %); tuned so the best horse wins about half the time'),
('race_off_surface_pct',      '5',       'Engine: speed penalty on a non-preferred surface'),
('race_stretch_effect_pct',   '3',       'Engine: how much a long stretch helps closers and a short one front runners'),
('max_field_size',            '12',      'Most horses in one race'),
('energy_race_base',          '10',      'Energy used by any race (%)'),
('energy_race_per_furlong',   '4',       'Extra Energy used per furlong raced (%)'),
('energy_race_max',           '60',      'Most Energy one race can use (%)'),
('recovery_divisor',          '5',       'Energy regained per day = Recovery stat / this (25 = 5%, 100 = 20%)'),
('schedule_days_ahead',       '7',       'Real days of race cards kept open for entries'),
('races_per_card',            '6',       'Races per track per race day'),
('purse_maiden',              '3000,10000', 'Purse range (Cash)'),
('purse_claiming',            '3000,10000', 'Purse range (Cash)'),
('purse_allowance',           '6000,15000', 'Purse range (Cash)'),
('claiming_prices',           '5000,7500,10000,15000,20000,25000', 'Claiming prices a race can carry'),
('ghost_stat_drop_max',       '10',      'Ghost horses roll foundation stats minus up to this much');
