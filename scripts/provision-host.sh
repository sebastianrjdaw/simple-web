#!/usr/bin/env bash
set -euo pipefail

if [[ ${EUID} -ne 0 ]]; then
  echo "Ejecuta este script con sudo." >&2
  exit 1
fi
if [[ "$(. /etc/os-release && echo "${ID}:${VERSION_ID}")" != "ubuntu:24.04" ]]; then
  echo "Aviso: este instalador está probado para Ubuntu 24.04 LTS." >&2
fi

apt-get update
apt-get install -y ca-certificates curl git openssl rsync
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
. /etc/os-release
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin chromium-browser avahi-daemon
systemctl enable --now docker avahi-daemon
systemctl mask sleep.target suspend.target hibernate.target hybrid-sleep.target

if ! id display >/dev/null 2>&1; then
  useradd --create-home --shell /bin/bash display
fi
install -d -o display -g display /home/display/.config/autostart
install -m 0755 /dev/stdin /usr/local/bin/simple-view-kiosk <<'KIOSK'
#!/usr/bin/env bash
xset s off
xset -dpms
xset s noblank
while true; do
  chromium --kiosk --noerrdialogs --disable-infobars --disable-session-crashed-bubble --autoplay-policy=no-user-gesture-required http://127.0.0.1/display
  sleep 2
done
KIOSK
install -o display -g display -m 0644 /dev/stdin /home/display/.config/autostart/simple-view.desktop <<'DESKTOP'
[Desktop Entry]
Type=Application
Name=Simple View
Exec=/usr/local/bin/simple-view-kiosk
X-GNOME-Autostart-enabled=true
DESKTOP
if [[ -f /etc/gdm3/custom.conf ]]; then
  sed -i '/^AutomaticLoginEnable=/d;/^AutomaticLogin=/d' /etc/gdm3/custom.conf
  sed -i '/\[daemon\]/a AutomaticLoginEnable=true\nAutomaticLogin=display' /etc/gdm3/custom.conf
fi

install -d -m 0750 /opt/simple-view
if [[ ! -f /opt/simple-view/.env ]]; then
  cp .env.example /opt/simple-view/.env
  key="base64:$(openssl rand -base64 32)"
  sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" /opt/simple-view/.env
fi
install -d -m 0770 /opt/simple-view/data/{database,media,thumbnails,backups,logs,cache}
rsync -a --delete --exclude '.env' --exclude 'data/' ./ /opt/simple-view/
cd /opt/simple-view
docker compose build
docker compose up -d
echo "Simple View preparado. Panel: http://$(hostname -I | awk '{print $1}')/admin"
echo "El usuario display abrirá Chromium en modo kiosco después del próximo reinicio."
