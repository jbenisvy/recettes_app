#!/bin/bash

# Script de déploiement automatique pour O2switch
ssh jutx2682@brome.o2switch.net "cd public_html/Recettes_Application && git pull origin main"
