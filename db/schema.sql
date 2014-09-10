CREATE TABLE IF NOT EXISTS "activities" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "handle" TEXT,
  "created_at" INTEGER,
  "crawled_at" timestamp,
  "premable" TEXT,
  "description" TEXT,
  "participants_min" INTEGER,
  "participants_max" INTEGER,
  "age_min" INTEGER,
  "age_max" INTEGER,
  "status" INTEGER,
  "chosen" INTEGER
);
CREATE INDEX IF NOT EXISTS "activities_handle" ON "activities" ("handle");

CREATE TABLE IF NOT EXISTS "activities_categories" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "activity_id" INTEGER,
  "category_id" INTEGER
);
CREATE INDEX IF NOT EXISTS "activities_categories_activity_id" ON "activities_categories" ("activity_id");

CREATE TABLE IF NOT EXISTS "activities_names" (
  "id"  INTEGER PRIMARY KEY AUTOINCREMENT,
  "activity_id" INTEGER,
  "name" TEXT
);

CREATE TABLE IF NOT EXISTS "activities_categories" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "activity_id" INTEGER,
  "category_id" INTEGER
);
CREATE INDEX IF NOT EXISTS "activities_categories_activity_id" ON "activities_categories" ("activity_id");

CREATE TABLE IF NOT EXISTS "attachments" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "activity_id" INTEGER,
  "original_url" TEXT,
  "uri" TEXT,
  "width" INTEGER,
  "height" INTEGER,
  "thumb_uri" TEXT,
  "thumb_width" TEXT,
  "thumb_height" TEXT,
  "file_size" INTEGER,
  "mime_type" INTEGER,
  "color" TEXT
);

CREATE TABLE "categories" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT,
  "handle" TEXT
);
