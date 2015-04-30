DROP TABLE IF EXISTS Likes;
DROP TABLE IF EXISTS Comments;
DROP TABLE IF EXISTS TaggedPhotos;
DROP TABLE IF EXISTS Tags;
DROP TABLE IF EXISTS Photos;
DROP TABLE IF EXISTS Albums;
DROP TABLE IF EXISTS Friends;
DROP TABLE IF EXISTS Users;
DROP TYPE IF EXISTS genders;


-- Assuming those are the only genders using the website...
CREATE TYPE genders AS ENUM ('male', 'female');

CREATE TABLE Users(
	"userId"			SERIAL PRIMARY KEY,

	-- Wikipedia says email max is 254
	"email"				VARCHAR(254) NOT NULL UNIQUE,

	-- Raw password for now, might look into hashing if there is time
	"password"			VARCHAR(254) NOT NULL,

	-- Assuming no one with a first or last ame longer than 255 chars will use it
	"firstName"			VARCHAR(255) NOT NULL,
	"lastName"			VARCHAR(255) NOT NULL,
	"birthDate"			DATE NOT NULL,
	"gender"			genders NOT NULL,


	-- Leaving City, State, and Country as 255 for international locations
	"homeCity"			VARCHAR(255) NOT NULL,
	"homeState"			VARCHAR(255) NOT NULL,
	"homeCountry"		VARCHAR(255) NOT NULL,

	"currentCity"		VARCHAR(255) NOT NULL,
	"currentState"		VARCHAR(255) NOT NULL,
	"currentCountry"	VARCHAR(255) NOT NULL
);

CREATE INDEX credentials ON Users("email", "password");

-- Assuming by "friendship", it is reciprocated, as opposed to following/follower relation
CREATE TABLE Friends(
	
	-- Smaller Id here
	"userId1"			INT PRIMARY KEY REFERENCES Users("userId"),

	-- Larger Id here
	"userId2"			INT REFERENCES Users("userId"),

	UNIQUE("userId1", "userId2")
);

CREATE TABLE Albums(
	"albumId"			SERIAL PRIMARY KEY,

	-- Assuming album name max 255
	"name"				VARCHAR(255),
	"userId"			INT REFERENCES Users("userId"),
	"creationDate"		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	"published"			BOOLEAN DEFAULT false
);


CREATE TABLE Photos(
	"photoId"			SERIAL PRIMARY KEY,
	"albumId"			INT REFERENCES Albums("albumId") ON DELETE CASCADE,
	"imgType"			VARCHAR(100),
	-- Maybe store UserId for convenience?
	"caption"			TEXT,
	"data"				BYTEA NOT NULL
);


CREATE TABLE Tags(
	"tagId"				SERIAL PRIMARY KEY,

	-- Assume max 50 characters
	"tag"				VARCHAR(50) NOT NULL
);
-- Can't enforce no-spaces, will do in code
CREATE UNIQUE INDEX lower_unique ON Tags (lower("tag"));


CREATE TABLE TaggedPhotos(
	"tagId"				INT REFERENCES Tags("tagId"),
	"photoId"			INT REFERENCES Photos("photoId") ON DELETE CASCADE,

	UNIQUE("tagId", "photoId")
);

-- Both indexes so they can both be queried
CREATE INDEX TP_tId ON TaggedPhotos ("tagId");
CREATE INDEX TP_pId ON TaggedPhotos ("photoId");



CREATE TABLE Comments(
	"commentId"			SERIAL PRIMARY KEY,
	"photoId"			INT REFERENCES Photos("photoId") ON DELETE CASCADE,
	"userId"			INT REFERENCES Users("userId"),

	-- Up to about 1GB (practically limitless)
	"comment"			TEXT NOT NULL,
	"commentDate"		TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE Likes(
	"userId"			INT REFERENCES Users("userId"),
	"photoId"			INT REFERENCES Photos("photoId") ON DELETE CASCADE,

	UNIQUE("photoId", "userId")
);

-- Both indexes so they can both be queried
CREATE INDEX L_uId ON Likes ("userId");
CREATE INDEX L_pId ON Likes ("photoId");



-- Insert admin account
INSERT INTO Users (
	"email",
	"password",
	"firstName", "lastName",
	"birthDate",
	"gender",

	"homeCity", "homeState", "homeCountry",

	"currentCity", "currentState", "currentCountry"
)
VALUES (
	'a@a.com',
	't',
	'Hiroki', 'Osame',
	'1993-02-27',
	'male'::genders,

	'Kobe', 'Hyogo', 'Japan',

	'Boston', 'MA', 'USA'
);
