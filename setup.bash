parent_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

read -p "Enter port for API: " apiport
read -p "Enter port for game: " gameport
read -p "Enter database name: " dbname
read -p "Enter UTORid: " utorid
read -s -p "Enter password: " password
echo -e "\n"
cd "$parent_path"
sed -i "s/dbnamehere/$dbname/g" index.js
sed -i "s/userhere/$utorid/g" index.js
sed -i "s/passwordhere/$password/g" index.js
sed -i "s/apiporthere/$apiport/g" index.js
sed -i "s/gameporthere/$gameport/g" controller.js
sed -i "s/gameporthere/$gameport/g" ww.js
