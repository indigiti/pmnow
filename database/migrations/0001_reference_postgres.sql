-- REFERENCE ONLY. Database mode is intentionally not active in M1-M3.
-- UUID values are migrated without remapping.
CREATE TABLE stories (
  id uuid PRIMARY KEY,
  type varchar(32) NOT NULL,
  status varchar(32) NOT NULL,
  headline varchar(500) NOT NULL,
  deck text,
  body text,
  source_id uuid,
  published_at timestamptz,
  created_at timestamptz NOT NULL,
  updated_at timestamptz NOT NULL
);

CREATE TABLE media (
  id uuid PRIMARY KEY,
  type varchar(32) NOT NULL,
  url text NOT NULL,
  caption text,
  credit text,
  position integer NOT NULL DEFAULT 0
);

CREATE TABLE story_media (
  story_id uuid NOT NULL REFERENCES stories(id),
  media_id uuid NOT NULL REFERENCES media(id),
  PRIMARY KEY (story_id, media_id)
);
