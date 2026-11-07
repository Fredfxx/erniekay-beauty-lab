/**
 * Vercel Serverless Function: /api/book
 * Handles appointment submissions and dispatches emails via Resend API
 */

export default async function handler(req, res) {
  // CORS Headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    const body = typeof req.body === 'string' ? JSON.parse(req.body) : (req.body || {});

    const fullName = (body.fullName || '').trim();
    const phone    = (body.phone || '').trim();
    const email    = (body.email || '').trim();
    const address  = (body.address || '').trim();
    const date     = (body.date || '').trim();
    const time     = (body.time || '').trim();
    const session  = (body.session || '').trim();
    const notes    = (body.notes || '').trim();

    if (!fullName || !phone || !address || !date || !time || !session) {
      return res.status(400).json({ success: false, message: 'Please fill in all required fields.' });
    }

    const serviceNames = {
      bridal: 'Bridal Makeup',
      hair: 'Hair Making & Styling',
      everyday: 'Everyday Glam',
      photo: 'Photo & Film Makeup',
      natural: 'Natural Glow Facial',
      lash: 'Lash & Brow Design',
      other: 'Other / Consultation'
    };
    const serviceTitle = serviceNames[session] || session;

    const RESEND_API_KEY = process.env.RESEND_API_KEY;
    const OWNER_EMAIL    = process.env.OWNER_EMAIL || 'alfredoteng027@gmail.com';
    const FROM_EMAIL     = process.env.FROM_EMAIL  || 'Erniekay Beauty Lab <onboarding@resend.dev>';

    const htmlContent = `
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
            <tr><td style="padding: 10px 0; color: #9BB0C7; width: 35%;">Client Name:</td><td style="padding: 10px 0; color: #fff; font-weight: bold;">${fullName}</td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Phone:</td><td style="padding: 10px 0; color: #E8D5B0; font-weight: bold;"><a href="tel:${phone}" style="color:#E8D5B0;">${phone}</a></td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Email:</td><td style="padding: 10px 0; color: #fff;">${email || '<em>Not provided</em>'}</td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Location / Address:</td><td style="padding: 10px 0; color: #fff;">${address}</td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Service:</td><td style="padding: 10px 0; color: #C9A96E; font-weight: bold;">${serviceTitle}</td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Preferred Date:</td><td style="padding: 10px 0; color: #fff; font-weight: bold;">${date}</td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Preferred Time:</td><td style="padding: 10px 0; color: #fff; font-weight: bold;">${time}</td></tr>
            <tr><td style="padding: 10px 0; color: #9BB0C7;">Special Notes:</td><td style="padding: 10px 0; color: #fff;">${notes || '<em>None</em>'}</td></tr>
          </table>
          <div style="text-align: center; margin-top: 24px;">
            <a href="https://wa.me/${phone.replace(/[^0-9]/g, '')}" style="display:inline-block; background: #25D366; color: #fff; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 14px;">
              💬 Chat with Client on WhatsApp
            </a>
          </div>
        </div>
      </div>
    </body>
    </html>
    `;

    const resendResponse = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${RESEND_API_KEY}`,
        'Content-Type': 'application/json',
        'User-Agent': 'ErniekayBooking/1.0'
      },
      body: JSON.stringify({
        from: FROM_EMAIL,
        to: [OWNER_EMAIL],
        subject: `🔔 New Booking: ${fullName} - ${serviceTitle} (${date})`,
        html: htmlContent
      })
    });

    const emailSent = resendResponse.ok;

    return res.status(200).json({
      success: true,
      message: 'Appointment booked successfully!',
      email_sent: emailSent,
      client_name: fullName,
      service: serviceTitle,
      date,
      time
    });
  } catch (err) {
    console.error('Booking error:', err);
    return res.status(500).json({ success: false, error: 'Internal server error' });
  }
}
