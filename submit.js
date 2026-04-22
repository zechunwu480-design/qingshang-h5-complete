/**
 * Cloudflare Worker: Handles form submission and forwards to Feishu webhook
 * Route: submit form to this worker's URL
 */
export default {
  async fetch(request, env) {
    if (request.method !== 'POST') {
      return new Response(JSON.stringify({ code: 405, msg: 'Method Not Allowed' }), {
        headers: { 'Content-Type': 'application/json' }
      });
    }

    try {
      const formData = await request.formData();
      const name = formData.get('name') || '';
      const company = formData.get('company') || '';
      const phone = formData.get('phone') || '';
      const service = formData.get('service') || '未选择';

      if (!name || !company || !phone) {
        return new Response(JSON.stringify({ code: 400, msg: '缺少必填字段' }), {
          headers: { 'Content-Type': 'application/json' }
        });
      }

      const phoneRegex = /^1[3-9]\d{9}$/;
      if (!phoneRegex.test(phone)) {
        return new Response(JSON.stringify({ code: 400, msg: '手机号格式错误' }), {
          headers: { 'Content-Type': 'application/json' }
        });
      }

      // Forward to Feishu webhook
      const webhook = 'https://open.feishu.cn/open-apis/bot/v2/hook/8a3faff5-65f1-473e-93dc-1a8b0655e9bf';
      const now = new Date().toLocaleString('zh-CN', { timeZone: 'Asia/Shanghai' });
      const msg = `📋 青商H5体检预约\n━━━━━━━━━━━━\n👤 姓名：${name}\n🏢 公司：${company}\n📱 手机：${phone}\n📌 预约服务：${service}\n🕐 提交时间：${now}`;

      await fetch(webhook, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ msg_type: 'text', content: { text: msg } })
      });

      return new Response(JSON.stringify({ code: 200, msg: '提交成功', redirect: '/success.html' }), {
        headers: {
          'Content-Type': 'application/json',
          'Access-Control-Allow-Origin': '*'
        }
      });

    } catch (err) {
      return new Response(JSON.stringify({ code: 500, msg: '服务器错误' }), {
        headers: { 'Content-Type': 'application/json' }
      });
    }
  }
};