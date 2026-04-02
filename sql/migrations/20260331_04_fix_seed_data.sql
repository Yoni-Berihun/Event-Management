-- Fix seed data: set event_end for all events, set existing RSVPs to approved,
-- insert Hawassa Rotaract events with event_end, add images.

USE city_events;

-- ── 1. Add event_end to all existing seed events (2-3 hours after start) ──────
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 3 HOUR) WHERE id = 1 AND event_end IS NULL;
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 2 HOUR) WHERE id = 2 AND event_end IS NULL;
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 8 HOUR) WHERE id = 3 AND event_end IS NULL;
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 3 HOUR) WHERE id = 4 AND event_end IS NULL;
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 4 HOUR) WHERE id = 5 AND event_end IS NULL;
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 5 HOUR) WHERE id = 6 AND event_end IS NULL;
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 6 HOUR) WHERE id = 7 AND event_end IS NULL;

-- ── 2. Fix existing RSVPs → approved (they were seeded before status column existed) ──
UPDATE rsvps SET status = 'approved', approved_at = created_at, approved_by = 1 WHERE status = 'pending';

-- ── 3. Insert Hawassa Rotaract events WITH event_end ─────────────────────────
INSERT IGNORE INTO events (organizer_id, title, description, image_path, location, event_date, event_end, capacity, is_verified, created_at) VALUES
(2,
 'Rotaract Coffee Meet-up - Piassa Konjo (Nov 22)',
 'Tomorrow''s forecast: lots of laughter, plenty of caffeine, and endless good vibes at our Rotaract coffee meet-up! Don''t miss out!',
 'uploads/event_coffee_nov22.jpg',
 'Piassa Konjo Coffee, In front of Lewi Hotel, Hawassa',
 '2023-11-22 17:00:00', '2023-11-22 19:00:00',
 40, 1, '2023-11-21 18:43:06'),

(2,
 'Rotaract Coffee Meet-up - Piassa Konjo (Nov 29)',
 'Get ready to caffeinate your mind and fuel your spirit at our Rotaract coffee meetup. It''s all about good vibes and great talks!',
 'uploads/event_coffee_nov29.jpg',
 'Piassa Konjo Coffee, In front of Lewi Hotel, Hawassa',
 '2023-11-29 17:00:00', '2023-11-29 19:00:00',
 40, 1, '2023-11-28 20:25:11'),

(2,
 'Culture Day Coffee Meet-up - Piassa Konjo',
 'Culture Day-themed coffee meet-up with cultural outfits. Be prompt, be fabulous, and enjoy a day of culture and chatter!',
 'uploads/event_culture_day_dec06.jpg',
 'Piassa Konjo Coffee, In front of Lewi Hotel, Hawassa',
 '2023-12-06 17:00:00', '2023-12-06 19:30:00',
 60, 1, '2023-12-05 18:00:00'),

(2,
 'Trivia & Karaoke Night - Golet Garden',
 'Quiz masters and karaoke enthusiasts unite for a night of fun and meaningful impact. Sing, test your wits, and help raise funds for community projects. Presented by Rotaract Club of Hawassa.',
 'uploads/event_trivia_karaoke_jan04.jpg',
 'Golet Garden (Kebele 05), Hawassa',
 '2024-01-04 17:00:00', '2024-01-04 21:00:00',
 150, 1, '2023-12-29 10:13:31');

-- ── 4. Add event_end to any Rotaract events that may have been inserted from old SQL ──
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 2 HOUR)
WHERE event_end IS NULL AND title LIKE '%Rotaract%';
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 4 HOUR)
WHERE event_end IS NULL AND title LIKE '%Trivia%';

-- ── 5. Catch-all: any event without event_end gets +2 hours ──────────────────
UPDATE events SET event_end = DATE_ADD(event_date, INTERVAL 2 HOUR) WHERE event_end IS NULL;
