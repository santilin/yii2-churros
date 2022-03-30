#!/bin/bash
DEST=src/helpers/YADTC.php
cp /var/www/html/red/app/lib/Fecha.php $DEST
sed -i "s/namespace app\\\\lib/namespace santilin\\\\churros\\\\helpers/g" $DEST
sed -i "s/Fecha/YADTC/g" $DEST
sed -i "s/.*FieldValueException;//g" $DEST
sed -i "s/FieldValueException/\Exception/g" $DEST
sed -i "s/function edad()/function age()/g" $DEST



