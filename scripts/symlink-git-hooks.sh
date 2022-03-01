#!/bin/sh
if [ ! -f .git/hooks/pre-commit ];
then
    echo "Pre-commit message install"
    echo "copying git-hooks/pre-commit to .git/hooks/pre-commit"
    cp scripts/git-hooks/pre-commit .git/hooks/pre-commit
else
    echo "Pre-commit message already exist."
fi