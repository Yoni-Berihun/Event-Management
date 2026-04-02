-- Seed data for City-Wide Event Tracking System
-- Hawassa City, Sidama Region, Ethiopia
-- All user passwords are: password

USE city_events;

-- ── Users ────────────────────────────────────────────────────────────────────
-- INSERT IGNORE skips rows whose id or email already exists (safe to re-run)

INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES
(1, 'Admin',          'admin@cityevents.local',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'admin'),
(2, 'Dawit Bekele',   'dawit@hawassa.et',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'organizer'),
(3, 'Sara Tesfaye',   'sara@hawassa.et',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'organizer'),
(4, 'Yonas Alemu',    'yonas@hawassa.et',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'organizer'),
(5, 'Meron Haile',    'meron@hawassa.et',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'attendee'),
(6, 'Biruk Tadesse',  'biruk@hawassa.et',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'attendee'),
(7, 'Hana Girma',     'hana@hawassa.et',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'attendee'),
(8, 'Abel Worku',     'abel@hawassa.et',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFutrnXBC', 'attendee');

-- ── Events ───────────────────────────────────────────────────────────────────

INSERT IGNORE INTO events (id, organizer_id, title, description, location, event_date, capacity, is_verified, created_at) VALUES
(1, 2,
 'Hawassa Lake Music Festival',
 'An open-air music celebration on the shores of Lake Hawassa featuring local and national artists. Enjoy live performances of traditional Sidama music, jazz, and contemporary Ethiopian beats as the sun sets over the lake. Food stalls and cultural exhibitions will be set up along the waterfront.',
 'Lake Hawassa Waterfront, Hawassa',
 '2026-04-05 17:00:00', 500, 1, '2026-03-01 09:00:00'),

(2, 2,
 'Sidama Coffee Ceremony Showcase',
 'Experience the full traditional Sidama coffee ceremony led by local coffee farmers from the surrounding highlands. Guests will learn about the origin of coffee in this region, the roasting process, and the cultural significance of bunna. Fresh injera and local snacks will be served.',
 'Hawassa Cultural Centre, Adare Village',
 '2026-04-12 10:00:00', 120, 1, '2026-03-02 11:00:00'),

(3, 3,
 'Hawassa University Tech & Innovation Summit',
 'A two-day summit bringing together students, researchers, and tech entrepreneurs from across the Sidama Region. Topics include agri-tech, renewable energy solutions for rural Ethiopia, and software development careers. Guest speakers from Addis Ababa and international universities will attend via video link.',
 'Hawassa University Main Hall, Hawassa',
 '2026-04-18 08:30:00', 300, 1, '2026-03-03 14:00:00'),

(4, 3,
 'Lake Hawassa Bird Watching & Nature Walk',
 'Guided early-morning bird watching walk along the lake edge, home to over 150 species including the African fish eagle, marabou stork, and pelicans. Suitable for all ages. Binoculars and field guides provided. Registration fee includes a light breakfast.',
 'Lake Hawassa Shore, Near Referral Hospital',
 '2026-04-26 06:00:00', 40, 1, '2026-03-04 08:00:00'),

(5, 4,
 'Traditional Sidama Cultural Night',
 'An evening of traditional Sidama dance, poetry, and storytelling celebrating the region\'s rich heritage. Performers in traditional costume will present the Fichchee-Chambalaalla new year rituals. Local food including chukamoo and woti will be on sale.',
 'Hawassa City Stadium Grounds',
 '2026-05-02 19:00:00', 800, 1, '2026-03-05 10:00:00'),

(6, 2,
 'Hawassa City Marathon 2026',
 'The annual Hawassa City Marathon returns with 5 km, 10 km, and full marathon categories open to all fitness levels. The route passes through the city centre, along the lake road, and finishes at Hawassa Stadium. Medals and prizes for top finishers in each category.',
 'Hawassa Stadium, Hawassa',
 '2026-05-10 06:30:00', 1000, 0, '2026-03-06 09:00:00'),

(7, 4,
 'Wolaita & Sidama Food Fair',
 'A celebration of southern Ethiopian cuisine featuring dishes from the Wolaita, Sidama, and Gedeo communities. Over 30 local restaurants and home cooks will showcase their specialties. Cooking demonstrations, recipe booklets, and a children\'s cooking competition throughout the day.',
 'Hawassa Exhibition Ground, Tabor Area',
 '2026-05-17 11:00:00', 600, 0, '2026-03-07 12:00:00');

-- ── RSVPs ────────────────────────────────────────────────────────────────────

INSERT IGNORE INTO rsvps (event_id, user_id, created_at) VALUES
-- Lake Music Festival (event 1)
(1, 5, '2026-03-05 10:15:00'),
(1, 6, '2026-03-05 11:30:00'),
(1, 7, '2026-03-06 09:00:00'),
(1, 8, '2026-03-06 14:20:00'),
-- Coffee Ceremony (event 2)
(2, 5, '2026-03-06 08:00:00'),
(2, 7, '2026-03-07 10:45:00'),
-- Tech Summit (event 3)
(3, 6, '2026-03-07 09:00:00'),
(3, 8, '2026-03-08 11:00:00'),
(3, 5, '2026-03-08 15:30:00'),
-- Bird Watching (event 4)
(4, 7, '2026-03-08 08:00:00'),
(4, 5, '2026-03-09 07:30:00'),
-- Cultural Night (event 5)
(5, 6, '2026-03-09 18:00:00'),
(5, 7, '2026-03-09 19:15:00'),
(5, 8, '2026-03-10 08:00:00');

-- ── Comments ─────────────────────────────────────────────────────────────────

INSERT IGNORE INTO comments (id, event_id, user_id, body, parent_comment_id, created_at) VALUES
-- Event 1: Music Festival
(1,  1, 5, 'So excited for this! Will there be parking near the waterfront?', NULL, '2026-03-06 10:00:00'),
(2,  1, 2, 'Yes, parking is available at the nearby Lewi Resort. Gates open at 4 PM.', 1, '2026-03-06 11:30:00'),
(3,  1, 6, 'Are kids allowed? Thinking of bringing my family.', NULL, '2026-03-07 09:00:00'),
(4,  1, 2, 'Absolutely! It is a family-friendly event. Under-12s enter free.', 3, '2026-03-07 10:15:00'),
(5,  1, 7, 'This is going to be amazing. The lake sunsets are beautiful.', NULL, '2026-03-08 14:00:00'),

-- Event 2: Coffee Ceremony
(6,  2, 5, 'Does the registration fee include all three rounds of coffee?', NULL, '2026-03-07 08:30:00'),
(7,  2, 2, 'Yes! All three rounds — abol, tona, and baraka — are included.', 6, '2026-03-07 09:45:00'),
(8,  2, 7, 'I have been to this before and it is absolutely worth it.', NULL, '2026-03-08 12:00:00'),

-- Event 3: Tech Summit
(9,  3, 6, 'Will the sessions be recorded for those who cannot attend both days?', NULL, '2026-03-08 10:00:00'),
(10, 3, 3, 'Yes, recordings will be posted on the Hawassa University website within a week.', 9, '2026-03-08 11:30:00'),
(11, 3, 8, 'Really looking forward to the agri-tech panel. Very relevant to our region.', NULL, '2026-03-09 09:00:00');
