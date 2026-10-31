-- A horse's best surface (surface_pref) is now hidden, revealed only by the trainer's notes.
-- The owner picks the surface it races on (racing_surface) and can change it.
ALTER TABLE qp_horses ADD COLUMN racing_surface ENUM('dirt','turf','steeplechase') NULL AFTER surface_pref;
-- Horses already in the game keep racing where they do now.
UPDATE qp_horses SET racing_surface = surface_pref;
INSERT INTO qp_settings (setting_key, setting_value, description) VALUES
('foundation_surface_weights', '50,30,20', 'Chance a new foundation horse is best on dirt,turf,steeplechase (relative weights)')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
