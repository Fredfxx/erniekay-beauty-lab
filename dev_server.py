"""
Local Development Server for Erniekay Beauty Lab
Serves static assets and handles /book.php (Resend email + local bookings.json)
"""
import http.server
import socketserver
import json
import urllib.request
import urllib.parse
import os
import datetime

PORT = 8080
DIRECTORY = os.path.dirname(os.path.abspath(__file__))

RESEND_API_KEY = os.environ.get("RESEND_API_KEY", "YOUR_RESEND_API_KEY_HERE")
OWNER_EMAIL = "alfredoteng027@gmail.com"
FROM_EMAIL = "Erniekay Beauty Lab <onboarding@resend.dev>"

BOOKINGS_FILE = os.path.join(DIRECTORY, "bookings.json")

SERVICE_NAMES = {
    'bridal': 'Bridal Makeup',
    'hair': 'Hair Making & Styling',
    'everyday': 'Everyday Glam',
    'photo': 'Photo & Film Makeup',
    'natural': 'Natural Glow Facial',
    'lash': 'Lash & Brow Design',
    'other': 'Other / Consultation'
}

class ErniekayHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)

    def send_cors_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_cors_headers()
        self.end_headers()

    def do_POST(self):
        # Handle booking endpoint
        if self.path in ['/book.php', '/api/book', 'book.php']:
            content_length = int(self.headers.get('Content-Length', 0))
            post_data = self.rfile.read(content_length)

            # Try parsing as JSON first, then url-encoded form data
            data = {}
            try:
                data = json.loads(post_data.decode('utf-8'))
            except Exception:
                parsed = urllib.parse.parse_qs(post_data.decode('utf-8'))
                data = {k: v[0] for k, v in parsed.items()}

            full_name = data.get('fullName', '').strip()
            phone = data.get('phone', '').strip()
            email = data.get('email', '').strip()
            address = data.get('address', '').strip()
            date = data.get('date', '').strip()
            time = data.get('time', '').strip()
            session = data.get('session', '').strip()
            notes = data.get('notes', '').strip()

            service_title = SERVICE_NAMES.get(session, session or 'General Session')

            # Save to local bookings.json
            new_booking = {
                'id': int(datetime.datetime.now().timestamp()),
                'full_name': full_name,
                'phone': phone,
                'email': email,
                'address': address,
                'service': service_title,
                'date': date,
                'time': time,
                'notes': notes,
                'status': 'pending',
                'created_at': datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            }

            bookings = []
            if os.path.exists(BOOKINGS_FILE):
                try:
                    with open(BOOKINGS_FILE, 'r', encoding='utf-8') as f:
                        bookings = json.load(f)
                except Exception:
                    bookings = []

            bookings.insert(0, new_booking)
            with open(BOOKINGS_FILE, 'w', encoding='utf-8') as f:
                json.dump(bookings, f, indent=2)

            # Send Email via Resend API
            email_sent = False
            try:
                html_body = f"""
                <!DOCTYPE html>
                <html>
                <body style="font-family: Arial, sans-serif; background-color: #0B192C; margin: 0; padding: 24px; color: #ffffff;">
                  <div style="max-width: 580px; margin: 0 auto; background: #112238; border-radius: 14px; border: 1px solid rgba(201, 169, 110, 0.4); overflow: hidden;">
                    <div style="background: linear-gradient(135deg, #1A1208, #2E1A06); padding: 28px; text-align: center; border-bottom: 2px solid #C9A96E;">
                      <h1 style="margin: 0; color: #E8D5B0; font-size: 22px;">✨ Erniekay Beauty Lab</h1>
                      <p style="margin: 6px 0 0; color: #C9A96E; font-size: 13px; text-transform: uppercase; letter-spacing: 2px;">New Appointment Request</p>
                    </div>
                    <div style="padding: 28px;">
                      <div style="display:inline-block; background: rgba(201,169,110,0.15); color: #E8D5B0; border: 1px solid #C9A96E; padding: 6px 14px; border-radius: 20px; font-size: 13px; margin-bottom: 18px; font-weight: bold;">
                        🔔 New Booking Alert
                      </div>
                      <table style="width: 100%; border-collapse: collapse;">
                        <tr><td style="padding: 10px 0; color: #9BB0C7; width: 35%;">Client Name:</td><td style="padding: 10px 0; color: #fff; font-weight: bold;">{full_name}</td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Phone:</td><td style="padding: 10px 0; color: #E8D5B0; font-weight: bold;"><a href="tel:{phone}" style="color:#E8D5B0;">{phone}</a></td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Email:</td><td style="padding: 10px 0; color: #fff;">{email or '<em>Not provided</em>'}</td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Location / Address:</td><td style="padding: 10px 0; color: #fff;">{address}</td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Service:</td><td style="padding: 10px 0; color: #C9A96E; font-weight: bold;">{service_title}</td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Preferred Date:</td><td style="padding: 10px 0; color: #fff; font-weight: bold;">{date}</td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Preferred Time:</td><td style="padding: 10px 0; color: #fff; font-weight: bold;">{time}</td></tr>
                        <tr><td style="padding: 10px 0; color: #9BB0C7;">Special Notes:</td><td style="padding: 10px 0; color: #fff;">{notes or '<em>None</em>'}</td></tr>
                      </table>
                      <div style="text-align: center; margin-top: 24px;">
                        <a href="https://wa.me/{phone.replace('+', '').replace(' ', '')}" style="display:inline-block; background: #25D366; color: #fff; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 14px;">
                          💬 Chat with Client on WhatsApp
                        </a>
                      </div>
                    </div>
                  </div>
                </body>
                </html>
                """

                payload = json.dumps({
                    'from': FROM_EMAIL,
                    'to': [OWNER_EMAIL],
                    'subject': f"🔔 New Booking: {full_name} - {service_title} ({date})",
                    'html': html_body
                }).encode('utf-8')

                req = urllib.request.Request(
                    'https://api.resend.com/emails',
                    data=payload,
                    headers={
                        'Authorization': f'Bearer {RESEND_API_KEY}',
                        'Content-Type': 'application/json',
                        'User-Agent': 'ErniekayBooking/1.0'
                    }
                )
                with urllib.request.urlopen(req, timeout=10) as resp:
                    email_sent = (resp.status == 200)
            except Exception as e:
                print(f"[Resend Error] {e}")

            response_json = json.dumps({
                'success': True,
                'message': 'Appointment booked successfully!',
                'email_sent': email_sent,
                'client_name': full_name,
                'service': service_title,
                'date': date,
                'time': time
            }).encode('utf-8')

            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_cors_headers()
            self.send_header('Content-Length', str(len(response_json)))
            self.end_headers()
            self.wfile.write(response_json)
            return

        super().do_POST()

    def do_GET(self):
        if self.path in ['/api/bookings', '/api/bookings/']:
            bookings = []
            if os.path.exists(BOOKINGS_FILE):
                try:
                    with open(BOOKINGS_FILE, 'r', encoding='utf-8') as f:
                        bookings = json.load(f)
                except Exception:
                    bookings = []
            resp_bytes = json.dumps(bookings).encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_cors_headers()
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

        super().do_GET()

if __name__ == '__main__':
    with socketserver.TCPServer(("", PORT), ErniekayHandler) as httpd:
        print(f"Erniekay Dev Server running on http://localhost:{PORT}")
        httpd.serve_forever()
