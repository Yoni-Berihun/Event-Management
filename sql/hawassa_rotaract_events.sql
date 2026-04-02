-- Extra seed data: events derived from Telegram export
-- Source: ChatExport_2026-03-11/messages.html (Rotaract Club of Hawassa)
-- All events are owned by organizer with id = 2 (Dawit Bekele).

USE city_events;

INSERT INTO events (
    organizer_id,
    title,
    description,
    image_path,
    location,
    event_date,
    capacity,
    is_verified,
    created_at
) VALUES
-- 1) Weekly coffee meet‑up – Nov 22, 2023
(2,
 'Rotaract Coffee Meet-up – Piassa Konjo (Nov 22)',
 'Tomorrow''s forecast: lots of laughter, plenty of caffeine, and endless good vibes at our Rotaract coffee meet-up! Don''t miss out! 😊☕️',
 'uploads/event_coffee_nov22.jpg',
 'Piassa Konjo Coffee, In front of Lewi Hotel, Hawassa',
 '2023-11-22 17:00:00',
 40,
 1,
 '2023-11-21 18:43:06'),

-- 2) Coffee meet‑up – Nov 29, 2023
(2,
 'Rotaract Coffee Meet-up – Piassa Konjo (Nov 29)',
 'Get ready to caffeinate your mind and fuel your spirit at our Rotaract coffee meetup. It''s all about good vibes and great talks! 😄☕️',
 'uploads/event_coffee_nov29.jpg',
 'Piassa Konjo Coffee, In front of Lewi Hotel, Hawassa',
 '2023-11-29 17:00:00',
 40,
 1,
 '2023-11-28 20:25:11'),

-- 3) Culture Day-themed coffee meet‑up – Dec 6, 2023
(2,
 'Culture Day Coffee Meet-up – Piassa Konjo',
 'Culture Day-themed coffee meet-up with cultural outfits. Be prompt, be fabulous, and enjoy a day of culture and chatter!',
 'uploads/event_culture_day_dec06.jpg',
 'Piassa Konjo Coffee, In front of Lewi Hotel, Hawassa',
 '2023-12-06 17:00:00',
 60,
 1,
 '2023-12-05 18:00:00'),

-- 4) Trivia & Karaoke Night – Jan 4, 2024
(2,
 'Trivia & Karaoke Night – Golet Garden',
 'Quiz masters and karaoke enthusiasts unite for a night of fun and meaningful impact. Sing, test your wits, and help raise funds for community projects. Presented by Rotaract Club of Hawassa in collaboration with Arada Beer.',
 'uploads/event_trivia_karaoke_jan04.jpg',
 'Golet Garden (Kebele 05), Hawassa',
 '2024-01-04 17:00:00',
 150,
 1,
 '2023-12-29 10:13:31');

