#!/usr/bin/env sh
#
# This is an scrpit for install swoft-releasecli
# More please see https://github.com/swoftlabs/swoft-releasecli
#
cd ~ || exit
# download tool
git clone https://github.com/swoftlabs/swoft-releasecli
# shellcheck disable=SC2164
cd swoft-releasecli
# add exec perm
chmod a+x bin/releasecli
# mv to env path
ln -s "$PWD"/bin/releasecli /usr/local/bin/releasecli
