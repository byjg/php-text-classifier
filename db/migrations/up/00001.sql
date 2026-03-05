-- @description: Create b8 wordlist table
CREATE TABLE b8_wordlist (
    token VARCHAR(255) NOT NULL,
    count_ham INTEGER DEFAULT NULL,
    count_spam INTEGER DEFAULT NULL,
    PRIMARY KEY (token)
);

INSERT INTO b8_wordlist (token, count_ham) VALUES ('b8*dbversion', 3);
INSERT INTO b8_wordlist (token, count_ham, count_spam) VALUES ('b8*texts', 0, 0);
