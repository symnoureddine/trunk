#!/bin/sh

BASH_PATH=$(dirname $0)
. $BASH_PATH/utils.sh
MB_PATH=$(cd $BASH_PATH/../; pwd);

# Compilation de ttf2ufm
make -C $MB_PATH/lib/dompdf/lib/ttf2ufm/src/ all

# Création du répertoires temporaires
cd $MB_PATH/tmp
mkdir tmp_fonts

cd tmp_fonts

# Récupération des polices
wget -i ../../shell/font_list.txt

check_errs $? "Failed to get the fonts" "Fonts getted!"

# Extraction de toutes ces polices
cabextract --lowercase *.exe

check_errs $? "Failed to extract fonts" "Fonts extracted!"

# Conversion en afm de ces polices et installation
#php ../../lib/dompdf/load_font.php Arial arial.ttf arialbd.ttf ariali.ttf arialbi.ttf
#php ../../lib/dompdf/load_font.php 'Comic Sans MS'  comic.ttf comicbd.ttf
#php ../../lib/dompdf/load_font.php Georgia georgia.ttf georgiab.ttf georgiai.ttf georgiaz.ttf
#php ../../lib/dompdf/load_font.php 'Trebuchet MS' trebuc.ttf trebucbd.ttf trebucit.ttf trebucbi.ttf
#php ../../lib/dompdf/load_font.php Verdana verdana.ttf verdanab.ttf verdanai.ttf verdanaz.ttf
#php ../../lib/dompdf/load_font.php 'Times New Roman' times.ttf timesbd.ttf timesi.ttf timesbi.ttf

# Nettoyage
cd ../
rm -Rf tmp_fonts

check_errs $? "Failed to remove tmp_fonts directory" "tmp_fonts directory removed!"