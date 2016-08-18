#!/bin/bash

PLUGIN=ContentSectioner

# SITES="kristenguycopywriting karametlen"
# SITES="retailnerds"
SITES="codeindependence"

for SITE in $SITES; do

	DISTDIR=~/Sites/$SITE/wp-content/plugins/$PLUGIN	

	mkdir -p $DISTDIR

	rm -rf $DISTDIR/*

	cp -R src/* $DISTDIR/

done