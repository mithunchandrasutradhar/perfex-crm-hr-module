# AiFace Device → Cloudflare WebSocket → Perfex CRM HR Attendance

**একদম Beginner-Friendly, Step-by-Step Setup + Testing + Troubleshooting Guide**  
**Version:** 1.0 | **Prepared:** 11 October 2026 | **Language:** বাংলা  
**উদ্দেশ্য:** নতুন Cloudflare account দিয়ে AiFace device-এর `ws://` WebSocket data গ্রহণ করে Perfex CRM HR Module-এর HTTPS API-তে পাঠানো।

> **স্ট্যাটাস (অবশ্যই পড়ুন):** এই প্রকল্পে Cloudflare Worker-এর WSS connection, `checklive`, `reg`, CRM API response, এবং `ws://` Port **80 ও 8080**-এর HTTP Upgrade handshake বাস্তবে পরীক্ষা করে সফল পাওয়া গেছে। **কিন্তু আসল AiFace device → Cloudflare connection এবং আসল attendance CRM database-এ save হওয়া এখনও এই পরীক্ষাগুলো দিয়ে প্রমাণিত হয়নি।** এগুলো সম্পন্ন করার ধাপ নিচে দেওয়া আছে। এটাকে পূর্ব-পরীক্ষিত production guarantee মনে করবেন না।

---

## 1. আমরা কী বানাচ্ছি?

```text
AiFace Fingerprint/Face Attendance Device
              |
              | ws:// (WebSocket, device initiates)
              v
Cloudflare Worker (WS Receiver / Bridge)
              |
              | HTTPS POST (JSON)
              v
Perfex CRM → HR Module → AiFace API
              |
              v
Device Mapping / Punch Log / Attendance
              |
              +---- CRM JSON ACK → Worker → Device (WebSocket)
```

- **Device-কে HTTP বা HTTPS protocol-এ বদলানোর দরকার নেই।** এই নির্দিষ্ট ডিভাইস পরীক্ষায় HTTP/HTTPS push আসেনি, WS দিয়ে এসেছে।
- Worker-এর কাজ শুধু **JSON গ্রহণ → CRM-এ forward → CRM response/ACK device-এ ফেরত দেওয়া**।
- Worker WebSocket URL-এর `/pub/chat` একটি **path**, Cloudflare Dashboard-এ আলাদা করে path create করতে হয় না। Code path চিনে নেয়।
- `ws://` **unencrypted**। Functional test করা যায়, কিন্তু বাস্তব employee attendance চালু করার আগে Security section পড়ুন।

### কী কী লাগবে

1. Cloudflare-এর Free account: <https://dash.cloudflare.com/sign-up>
2. Perfex CRM এবং সংশ্লিষ্ট HR Module ইনস্টল/সক্রিয়। Repo: <https://github.com/mithunchandrasutradhar/perfex-crm-hr-module/tree/main/modules/hr_module>
3. HR Module-এ AiFace Integration enabled, একটি **Active** AiFace Device ও তার serial number registered, Employee ↔ Device User ID mapped।
4. AiFace device-এ **MENU → Comm set → Server** settings পরিবর্তনের permission।
5. Windows PC-তে CMD এবং WebSocket testing-এর জন্য Hoppscotch: <https://hoppscotch.io/realtime/websocket>
6. একটি বাস্তব (কিন্তু test-only) employee/device user mapping দিয়ে attendance যাচাই করার সুযোগ।

## 2. নতুন Cloudflare Account থেকে Worker তৈরি

1. <https://dash.cloudflare.com/> এ login করুন।
2. **Workers & Pages** খুলুন।
3. **Create application** চাপুন।
4. **Start with Hello World!** নির্বাচন করুন (GitHub, GitLab, Static Files নয়)।
5. Worker name দিন, যেমন **`aiface-ws-bridge`** (অন্য নাম হলেও চলবে)।
6. **Deploy** চাপুন।
7. Worker-এর Overview-এ `https://aiface-ws-bridge.<YOUR_SUBDOMAIN>.workers.dev`-এর মতো URL পাবেন। **নিজের আসল URL লিখে রাখুন।**
8. ওপরের **Edit code**-এ গিয়ে default Hello World code মুছে পরের section-এর JavaScript paste করুন।

> **PLACEHOLDER RULE:** Guide-এ `<YOUR_SUBDOMAIN>`, `<YOUR_CRM_DOMAIN>`, `<YOUR_DEVICE_SERIAL>` লেখা থাকলে নিজের তথ্য বসাতে হবে। `<` বা `>` চিহ্ন URL-এ রাখা যাবে না।

## 3. Cloudflare Worker-এর সম্পূর্ণ JavaScript Code

এই এক-Worker code হলো **প্রাথমিক কার্যকর bridge**। এটি `reg`, `checklive`, `sendlog`-সহ `cmd`+`sn` সম্বলিত JSON CRM-এর বিদ্যমান endpoint-এ পাঠায়; CRM যে ACK দেয় সেটি ফেরত দেয়। Event ordering, timeout এবং মূল সমস্যা বোঝার জন্য সংক্ষিপ্ত logs রাখা হয়েছে। দীর্ঘদিন একই WebSocket খোলা রেখে ৫০-এর বেশি CRM request পাঠানোর সীমাবদ্ধতা Section 11-তে আলোচনা করা হয়েছে।

**Edit Code → `worker.js` → সব মুছে paste → Deploy:**

```javascript
async function forwardPacket(packet, env) {
  if (!packet || typeof packet !== "object" || !packet.cmd || !packet.sn) {
    throw new Error("Invalid command or serial");
  }
  if (!env.DEVICE_SN || packet.sn !== env.DEVICE_SN) {
    throw new Error("Device serial not allowed");
  }
  if (!env.CRM_URL || !env.CRM_URL.startsWith("https://")) {
    throw new Error("CRM_URL must use HTTPS");
  }

  const res = await fetch(env.CRM_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(packet),
    signal: AbortSignal.timeout(15000),
    redirect: "error"
  });

  if (!res.ok) {
    throw new Error("CRM returned HTTP " + res.status);
  }

  const ack = await res.json();
  if (!ack || ack.ret !== packet.cmd || ack.result === false) {
    throw new Error("Invalid or rejected CRM ACK");
  }
  return ack;
}

async function handleMessage(rawData, socket, env) {
  let packet;
  try {
    if (typeof rawData !== "string") {
      throw new Error("Binary message not supported by this bridge");
    }
    packet = JSON.parse(rawData);
    console.log("Received command:", packet?.cmd || "unknown");

    const ack = await forwardPacket(packet, env);
    console.log("CRM accepted command:", packet.cmd);

    if (socket.readyState === 1) {
      socket.send(JSON.stringify(ack));
    }
  } catch (error) {
    console.error("WS/CRM error:", String(error.message || error));
    if (socket.readyState === 1 && packet?.cmd) {
      socket.send(JSON.stringify({ ret: packet.cmd, result: false }));
    }
  }
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    if (url.pathname !== "/pub/chat") {
      return new Response("Not found", { status: 404 });
    }
    if (request.headers.get("Upgrade")?.toLowerCase() !== "websocket") {
      return new Response("WebSocket required", { status: 426 });
    }

    const [client, server] = Object.values(new WebSocketPair());
    server.accept();
    console.log("WebSocket client connected");

    // Process device commands in the order received.
    let pending = Promise.resolve();
    server.addEventListener("message", (event) => {
      pending = pending
        .then(() => handleMessage(event.data, server, env))
        .catch((error) => console.error("Queue error:", error.message));
    });
    server.addEventListener("close", () => {
      console.log("WebSocket client disconnected");
    });
    server.addEventListener("error", () => {
      console.error("WebSocket connection error");
    });

    return new Response(null, { status: 101, webSocket: client });
  }
};
```

**Code কী করে:**

- `/pub/chat` ছাড়া 404 দেয়, upgrade ছাড়া 426 দেয়।
- WebSocket handshake সফল হলে `101 Switching Protocols` দেয়।
- JSON-এর `sn` variable-এর `DEVICE_SN`-এর সঙ্গে মেলে কি না দেখে; এটি **শুধু allowlist, secure authentication নয়**।
- `fetch(CRM_URL)` দিয়ে HTTPS POST করে; CRM-এর `ret` একই হলে এবং `result !== false` হলে ACK ফেরত দেয়।
- CRM failure হলে device-এ `result:false` পাঠানোর চেষ্টা করে; device কীভাবে retry করে তা firmware অনুযায়ী test করতে হবে।
- ব্যক্তিগত attendance payload পুরোটা console log করে না।

> **নোট:** এই guide-এর code পূর্বে কথোপকথনে সফলভাবে test হওয়া logic-এর পরিমার্জিত version; নতুন ordering/logging পরিবর্তনগুলো এখানে দেওয়া হলেও live device দিয়ে আলাদাভাবে পরীক্ষা করতে হবে।

## 4. Variables and Secrets সেট করুন

1. Worker Overview → **Settings** → **Variables and Secrets**।
2. **Add variable** চাপুন। Production environment নির্বাচন করা আছে কি না দেখুন।
3. দুটি variable যোগ করুন:

| Name | Example Value | প্রয়োজন |
|---|---|---|
| `CRM_URL` | `https://<YOUR_CRM_DOMAIN>/hr_module/aiface/receive` | CRM-এর গ্রহণকারী endpoint |
| `DEVICE_SN` | `<YOUR_DEVICE_SERIAL>` | Device serial allowlist |

4. Save করুন; চাইলে `DEVICE_SN` Secret type করতে পারেন, কিন্তু **serial number নিজে authentication key নয়**।
5. Variable পরিবর্তনের পরে প্রয়োজনমতো **Deploy** বা Save/Deploy করে নতুন deployment active হয়েছে কি না যাচাই করুন।

**CRM endpoint কোথা থেকে এল?** Linked HR Module-এর `controllers/Aiface.php`-এ `receive()` method ও `POST /hr_module/aiface/receive` উল্লেখ আছে। আপনার module-version/routing ভিন্ন হলে আগে যাচাই করুন:  
<https://raw.githubusercontent.com/mithunchandrasutradhar/perfex-crm-hr-module/main/modules/hr_module/controllers/Aiface.php>

**CRM-এ আগে নিশ্চিত করুন:**

- HR Module-এর AiFace integration **Enabled**।
- Device record-এর serial হুবহু সঠিক ও status **Active**।
- Employee form/Device Mapping-এ device-এর `enrollid` ঠিক employee-এর সঙ্গে mapped।
- CRM-এর PHP/DB Time Zone ও device clock একই বাস্তব সময়ের কাছাকাছি। বর্তমান linked model ৬ ঘণ্টার বেশি পুরোনো/ভবিষ্যতের punch বাদ দেয়।

## 5. URL বোঝা (নিজের Cloudflare URL থেকে)

ধরা যাক Worker-এর Overview-এ পাওয়া গেছে:

```text
https://aiface-ws-bridge.MYACCOUNT.workers.dev
```

তার WebSocket URL:

```text
wss://aiface-ws-bridge.MYACCOUNT.workers.dev/pub/chat
ws://aiface-ws-bridge.MYACCOUNT.workers.dev:8080/pub/chat
```

| অংশ | মানে |
|---|---|
| `aiface-ws-bridge.MYACCOUNT.workers.dev` | আপনার Cloudflare Worker hostname |
| `/pub/chat` | JavaScript code-এ hardcoded endpoint path |
| `wss://` | Secure WebSocket, সাধারণত port 443 |
| `ws://...:8080` | Plain WebSocket, port 8080; **encrypted নয়** |

**কেন 8080?** পরীক্ষায় Cloudflare 8080-তে HTTP→WebSocket upgrade (`101`) দিয়েছিল। Cloudflare network documentation-এ 8080 একটি supported HTTP proxy port। Device firmware-এ এটি কাজ করে কি না **আলাদা real-device test** প্রয়োজন। Port `7788` Cloudflare Worker-এ ইচ্ছামতো bind করা যায় না।

## 6. Test A — HTTP request-এ 426 পাওয়া (Expected)

এটি Device-এর HTTP protocol test **নয়**, শুধু Worker path ঠিক আছে কি না যাচাই। Windows CMD খুলুন, নিজের hostname বসিয়ে চালান:

```bat
curl.exe -i http://aiface-ws-bridge.MYACCOUNT.workers.dev:8080/pub/chat
```

Expected:

```text
HTTP/1.1 426 Upgrade Required
...
WebSocket required
```

**ব্যাখ্যা:** Worker path-এ পৌঁছেছে, কিন্তু WebSocket Upgrade header নেই বলে 426। Root URL `/` খুলে `Not found` পেলেও স্বাভাবিক।

## 7. Test B — Port 8080 WS Handshake (সবচেয়ে গুরুত্বপূর্ণ network test)

Windows CMD-তে পুরো command **এক লাইনে** চালান; hostname নিজেরটা দিন:

```bat
curl.exe -i -N --http1.1 --max-time 5 -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Sec-WebSocket-Version: 13" -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" http://aiface-ws-bridge.MYACCOUNT.workers.dev:8080/pub/chat
```

Expected:

```text
HTTP/1.1 101 Switching Protocols
Connection: upgrade
Upgrade: websocket
Sec-WebSocket-Accept: ...
...
curl: (28) Operation timed out after 5000 milliseconds ...
```

- **`101 Switching Protocols` = handshake success**।
- ৫ সেকেন্ড পরে `curl (28)` timeout স্বাভাবিক; এই command WebSocket frame পাঠায় না।
- `301/302/307/308` হলে redirect হচ্ছে; plain WS device-এর সমস্যা হতে পারে।
- `403`, `404`, `525`, timeout ইত্যাদি হলে URL, code deployment, port, Cloudflare routing/logs দেখুন।
- এটি handshake-only test; device register বা attendance save **প্রমাণ করে না**।

## 8. Test C — Hoppscotch-এ WSS Messaging + CRM API

1. <https://hoppscotch.io/realtime/websocket> খুলুন।
2. WebSocket URL দিন: `wss://aiface-ws-bridge.MYACCOUNT.workers.dev/pub/chat`।
3. **Connect** চাপুন। `Connected` আসা উচিত।
4. নিচের payload **JSON Message box**-এ paste করে Send করুন।

### C1. ভুল serial দিয়ে negative test (CRM-এ না পাঠানোর test)

```json
{"cmd":"checklive","sn":"TEST-DEVICE"}
```

Expected:

```json
{"ret":"checklive","result":false}
```

`result:false` এখানে **সফল security/validation test**—এটি প্রকৃত attendance নয়।

### C2. আসল serial দিয়ে CRM-এর `checklive` test

```json
{"cmd":"checklive","sn":"<YOUR_DEVICE_SERIAL>"}
```

Expected (clock কেবল example):

```json
{"ret":"checklive","cloudtime":"2026-10-11 16:48:45"}
```

- `ret:checklive` + `cloudtime` মানে WebSocket → Worker → CRM → Worker → Hoppscotch round trip কাজ করেছে।
- `checklive` response-এ `result:true` থাকা **বাধ্যতামূলক নয়**; linked CRM code শুধু `ret` ও `cloudtime` দেয়।

### C3. Device registration (`reg`) test

```json
{
  "cmd": "reg",
  "sn": "<YOUR_DEVICE_SERIAL>",
  "devinfo": {"modelname":"AiFace"}
}
```

Expected (Cloud time পরিবর্তিত হবে):

```json
{
  "ret": "reg",
  "tryseconds": 300,
  "result": true,
  "cloudtime": "2026-10-11 16:50:10",
  "nosenduser": true,
  "nosendlog": false,
  "nosendimage": true
}
```

- `result:true` মানে **CRM registration command গ্রহণ করেছে**; এটি physical device connectivity প্রমাণ করে না।
- `tryseconds` আপনার CRM module/firmware সংস্করণভেদে ভিন্ন হতে পারে।

### C4. Worker Logs Test

Dashboard → Worker → **Observability → Logs** (কিছু UI-তে Logs নামে দেখা যায়)। Live-tail বা recent logs দেখুন। উদাহরণ:

```text
WebSocket client connected
Received command: checklive
CRM accepted command: checklive
Received command: reg
CRM accepted command: reg
```

Logs না দেখালে Worker version active কি না, Observability Logs enabled কি না, এবং আবার request এসেছে কি না দেখুন। পূর্ণ employee payload log করবেন না।

## 9. Test D — CRM-এ সত্যিকার Attendance Save পরীক্ষা

**এই ধাপ production CRM-এর record পরিবর্তন করতে পারে।** আগে Backup/পরীক্ষার employee ব্যবহার করুন। শুধু `ret:sendlog` + `result:true` দেখলেই Attendance save সফল ধরা যাবে না।

### D1. Employee Mapping যাচাই

1. CRM → HR Module → Attendance Devices-এ AiFace Device Active আছে কিনা দেখুন।
2. Employee record / Device Mapping-এ নিজের **real device enrollid** সঠিকভাবে map করুন (যেমন test employee ID `1000`; কিছু device-এ `0` থাকে—সেটিও ইচ্ছাকৃত mapping কি না যাচাই করুন)।
3. Device time, CRM server time, timezone মিলিয়ে নিন। বর্তমান model **৬ ঘণ্টার বাইরে** timestamp বাদ দেয় এবং accepted punches-এর জন্য **server-side বর্তমান time** ব্যবহার করে।

### D2. একটি test punch পাঠান (কেবল mapped test employee)

Hoppscotch-এর WSS session-এ এই JSON paste করুন; **`<...>` placeholder নিজের value দিয়ে বদলাতে হবে**। `time` অবশ্যই CRM server-এর বর্তমান সময়ের কাছাকাছি হবে।

```json
{
  "cmd": "sendlog",
  "sn": "<YOUR_DEVICE_SERIAL>",
  "count": 1,
  "logindex": 0,
  "record": [
    {
      "enrollid": 1000,
      "name": "",
      "time": "<CURRENT_TIME_YYYY-MM-DD HH:mm:ss>",
      "mode": 0,
      "inout": 0,
      "event": 2
    }
  ]
}
```

**Expected protocol ACK:**

```json
{
  "ret": "sendlog",
  "result": true,
  "count": 1,
  "logindex": 0,
  "mark": true,
  "cloudtime": "<SERVER_TIME>"
}
```

**অবশ্যই এরপর CRM-এ দেখবেন:**

1. **Attendance Devices → Sync Logs** (সুনির্দিষ্ট menu version অনুযায়ী বদলাতে পারে): `records_fetched:1` এবং `records_saved` কত? **`records_saved > 0` না হলে save-এর প্রমাণ নেই।**
2. Employee-এর **Attendance** পেজে সংশ্লিষ্ট date এবং Punch Log/View Log।
3. DB access থাকলে (কেবল read-only query): prefix-সহ `hr_zkteco_sync_logs`, `hr_zkteco_punches`, `hr_attendance` table। Actual prefix অনেক ক্ষেত্রে `tbl` হয়; ইচ্ছেমতো SQL insert/update করবেন না।
4. **Repeated `sendlog` test করবেন না** যদি duplicate protection না থাকে। Replayed punch `hr_zkteco_punches`-এ duplicate হতে পারে।

**গুরুত্বপূর্ণ বাস্তবতা:** Current `Aiface.php`-তে `sendlog`-এর `result:true` সাধারণভাবে পাঠানো হয়, এমনকি processing-এর পরে saved count `0` হলেও। তাই **CRM-এর saved count ও Attendance table-ই মূল verdict**।

## 10. Test E — AiFace Device-এ Server Domain/Port বসানো

Manual screenshot-এ menu path: **MENU → Comm set → Server**। প্রথমে পুরোনো Domain/Port লিখে রাখুন, যাতে rollback করা যায়।

| Device setting | Test value |
|---|---|
| **Server Req** | `Yes` |
| **Use domainNm** | `Yes` |
| **DomainNm** | `aiface-ws-bridge.MYACCOUNT.workers.dev` |
| **SerPortNo** | `8080` |
| **Heartbeat** | `3` বা `5` (manual ও device অনুযায়ী) |
| **Server approval** | `No` (manual-এর example; device policy বুঝে ব্যবহার) |

**খুব গুরুত্বপূর্ণ:** `DomainNm` ঘরে `ws://` লিখবেন না, `:8080` লিখবেন না, `/pub/chat`-ও লিখবেন না। Port আলাদা `SerPortNo` ঘরে বসাবেন। **Device যদি নিজে `/pub/chat` path ব্যবহার করে, তবেই এই endpoint-এ পৌঁছাবে।** আগের capture-এ `/pub/chat` দেখা গিয়েছিল; নতুন Cloudflare connection-এ confirm করতে হবে।

**করণীয়:**

1. Device network/DNS resolution ঠিক আছে নিশ্চিত করুন।
2. Settings Save করুন। প্রয়োজন হলে device Restart করুন।
3. Cloudflare Dashboard → Worker → **Observability → Logs** খোলা রাখুন।
4. Device থেকে `reg`/Heartbeat পাঠানোর সুযোগ দিন। Worker-এ `WebSocket client connected` এবং `Received command: reg` লগ এলে এগোন।
5. একটি **নতুন, real** attendance punch দিন। `Received command: sendlog` ও `CRM accepted command: sendlog` দেখুন।
6. CRM Sync Log-এ `records_saved`, Attendance এবং Punch Log মিলিয়ে দেখুন।
7. Device offline হলে/কোনো log না এলে পুরোনো settings restore করুন এবং Section 12 অনুসরণ করুন।

### Device test success-এর সংজ্ঞা

শুধু Cloudflare-এর request count বাড়া বা device-এ Online দেখা **যথেষ্ট নয়**। End-to-end success বলতে একসঙ্গে চারটি প্রমাণ চাই:

- [ ] Actual device থেকে Worker-এ `reg` পৌঁছেছে।
- [ ] Actual device থেকে Worker-এ `sendlog` পৌঁছেছে।
- [ ] CRM-side Sync Log-এ ঐ batch-এর `records_saved > 0`।
- [ ] Employee Attendance/Punch Log-এ নির্দিষ্ট punch দৃশ্যমান।

## 11. Free Plan, Long-Lived WS এবং 100 hits/day-এর গুরুত্বপূর্ণ সীমা

- Cloudflare Workers Free-তে বর্তমানে **100,000 inbound Worker requests/day** quota আছে; মাত্র 100 punch/day হলে request volume সাধারণত কম। কিন্তু **`100 punches = 100 Worker HTTP requests` ধরে নেওয়া যাবে না**। একটা WebSocket দীর্ঘদিন খুলে থাকলে একটি handshake-এর মধ্যে অনেক frame আসতে পারে।
- **কঠিন সীমা:** Workers Free-তে **এক Worker invocation-এ 50 external subrequests**। Single WebSocket session-এর ভেতর প্রতি `sendlog`/`reg`/`checklive` CRM-এ একটি করে `fetch()` করলে 50-এর বেশি command-এ limit hit হতে পারে, যদিও দিনে মাত্র 100 punch হয়। Redirect হলেও অতিরিক্ত subrequest গণনা হতে পারে।
- **অতিরিক্ত সমাধান (developer/advanced):** দুই Worker ব্যবহার করুন: Worker A শুধু WS গ্রহণ করবে, **Service Binding** দিয়ে Worker B-তে message পাঠাবে; Worker B প্রতি invocation-এ CRM-এর HTTPS fetch করবে। Service Binding-এর internal subrequests-এর quota আলাদা। এই architecture-এর details নিচে দেওয়া আছে। এটি এই প্রকল্পে live-device দিয়ে **এখনও test হয়নি**।
- সাধারণ Worker long-lived WS connection যেকোনো সময় disconnect হতে পারে; device-এ reconnect/retry বা Durable Objects Hibernation ব্যবস্থার প্রয়োজন হতে পারে।

### Advanced: 2-Worker workaround (50 external fetch per WebSocket session এড়াতে)

**এটি optional। প্রথমে Step 2–10 দিয়ে সফল handshake, CRM API, real device যাচাই করুন। তারপরে traffic দরকার হলে করুন।**

1. `aiface-crm-forwarder` নামে আরেকটি Cloudflare Worker **Create → Hello World → Deploy** করুন।
2. এই **Forwarder Worker**-এর Code:

```javascript
export default {
  async fetch(request, env) {
    if (request.method !== "POST") {
      return new Response("Method not allowed", { status: 405 });
    }
    try {
      const packet = await request.json();
      if (!packet?.cmd || !packet?.sn || packet.sn !== env.DEVICE_SN) {
        return Response.json({ ret: packet?.cmd || "", result: false }, { status: 403 });
      }
      if (!env.CRM_URL?.startsWith("https://")) {
        throw new Error("CRM URL invalid");
      }
      const crm = await fetch(env.CRM_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(packet),
        signal: AbortSignal.timeout(15000),
        redirect: "error"
      });
      if (!crm.ok) throw new Error("CRM returned HTTP " + crm.status);
      const ack = await crm.json();
      if (ack?.ret !== packet.cmd || ack.result === false) {
        throw new Error("CRM rejected command");
      }
      return Response.json(ack);
    } catch (error) {
      console.error("Forwarder error:", error.message);
      return Response.json({ ret: "", result: false }, { status: 502 });
    }
  }
};
```

3. Forwarder Worker-এর **Settings → Variables and Secrets**-এ `CRM_URL`, `DEVICE_SN` বসান; Deploy করুন।
4. `aiface-ws-bridge` Worker-এর **Settings → Bindings → Add binding → Service →** Target হিসেবে `aiface-crm-forwarder` নির্বাচন করুন; Binding variable name দিন **`CRM_SVC`**। UI-এর সঠিক label বদলাতে পারে।
5. WS Worker-এর Step 3 code-এর **`forwardPacket` function**-টি পুরোটা replace করুন:

```javascript
async function forwardPacket(packet, env) {
  if (!packet?.cmd || !packet?.sn || packet.sn !== env.DEVICE_SN) {
    throw new Error("Device serial not allowed");
  }
  if (!env.CRM_SVC) throw new Error("Missing CRM_SVC Service Binding");
  const res = await env.CRM_SVC.fetch(
    new Request("https://internal.example/forward", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(packet)
    })
  );
  if (!res.ok) throw new Error("Forwarder returned HTTP " + res.status);
  const ack = await res.json();
  if (ack?.ret !== packet.cmd || ack.result === false) {
    throw new Error("Forwarder rejected command");
  }
  return ack;
}
```

6. WS Worker-এ `DEVICE_SN` রাখুন; `CRM_URL` এখন Forwarder-এ থাকবে। **Deploy** করে Test C1/C2/C3 এবং Test D পুনরায় চালান।
7. Forwarder-এর **workers.dev public route disable** করুন (যদি UI-তে সম্ভব হয়) অথবা শক্ত authorization/authentication দিয়ে direct public POST বন্ধ করুন। শুধু Service Binding থাকা মানেই তার public URL নিরাপদ হয়ে যায় না।

> **Advanced path caveat:** Service Binding approach Free plan-এর per-invocation external-fetch সীমা লাঘব করে, কিন্তু নিরাপত্তা, firmware compatibility, DB duplicate prevention, retry/queue ও device reconnection-এর বিকল্প নয়। Performance ও quota Cloudflare-এর বর্তমান নীতিমালা দেখে পুনরায় যাচাই করুন।

## 12. Troubleshooting Matrix

| সমস্যা | সবচেয়ে সম্ভাব্য কারণ | সমাধান |
|---|---|---|
| Browser/Preview-তে `Not found` | `/` path; Worker শুধু `/pub/chat` চেনে | `/pub/chat` ব্যবহার করুন; browser-এ WS message test হবে না |
| HTTP-তে `426 Upgrade Required` | Request-এ WS Upgrade নেই | Normal/expected; Test B handshake দিন |
| Test B-তে `101` তারপর `curl (28)` | Handshake হয়েছে; frame পাঠানো হয়নি | Expected; Hoppscotch Message test করুন |
| Hoppscotch `Connected`, কিন্তু `result:false` | ভুল serial/CRM failed | `DEVICE_SN`, CRM Logs, allowed device দেখুন |
| `checklive` কাজ করে, `reg` fail | CRM integration/active device/reg processing | CRM `Aiface.php` ও Sync Logs দেখুন |
| `reg result:true`, punch নেই | Device mapping নেই, timestamp stale, worker/CRM code error | Sync Log `records_saved`, timestamp, enrollment mapping চেক |
| `sendlog result:true` কিন্তু Attendance নেই | CRM-এ saved count 0 | `records_fetched` বনাম `records_saved` দেখুন; ACK একা প্রমাণ নয় |
| 8080-তে `101`, কিন্তু Device offline | Device DNS/TCP, WS path, firmware, network block | Workers live logs; Device DNS, domain/port, firmware path যাচাই |
| Device থেকে `/pub/chat` নয় অন্য path | Firmware hard-coded path ভিন্ন | Worker logs-এ request path log করে code routing সামঞ্জস্য করুন |
| CRM response HTML / parser error | CRM route/login redirect, PHP error, WAF | CRM URL খুলে/CRM logs দেখে 200 JSON নিশ্চিত করুন |
| Worker `CRM returned HTTP 403/429` | Device not registered, integration disabled/rate limit | CRM device authorization + rate logs চেক |
| Worker fetch-limit error (50+) | একই WS invocation থেকে অতিরিক্ত external fetch | Section 11-এর 2-Worker Service Binding architecture |
| Repeated punch/duplicate | Retry/backlog, DB dedup নেই | Idempotency/dedup যুক্ত করুন; repeated test এড়ান |
| Device পুরোনো attendance পাঠায় | Offline backlog / clock drift | Device clock; CRM backlog policy; save logic পরিকল্পিতভাবে বদলান |
| Mixed Content error browser test | HTTPS page থেকে plain `ws://` blocked | Browser test-এ `wss://` ব্যবহার; Port 8080 CMD দিয়ে test |

## 13. Production Security ও Reliability Checklist (অত্যন্ত গুরুত্বপূর্ণ)

**এই checklist শেষ না করে বাস্তব employee attendance data-কে production-safe ঘোষণা করবেন না।**

- [ ] **Transport encryption:** `ws://` Port 8080-তে ব্যক্তিগত তথ্য plaintext যায়। Device firmware WSS/SSL সমর্থন করলে `wss://` (যেমন port 8443; আগে test) ব্যবহার করুন। Firmware WSS সমর্থন না করলে trusted private network/VPN/secure gateway বা অন্য hosting/architecture বিবেচনা করুন। **Free plain WS-to-HTTP bridge network encryption তৈরি করে না।**
- [ ] **Strong device identity:** শুধু `sn` client নিজে পাঠায়; সহজে spoof করা যায়। Firmware-এ কোনো credential/signature/cert mechanism আছে কি না vendor-এর সঙ্গে যাচাই করুন। সম্ভব হলে client authentication, IP controls, network ACL এবং replay defenses যুক্ত করুন।
- [ ] **CRM receiver security:** `/hr_module/aiface/receive` endpoint বর্তমান repo-তে unauthenticated public receiver। Device allowlist/rate limit আছে, কিন্তু internet-exposed endpoint audit/hardening দরকার। CRM API-তে signature/shared secret চাওয়া হলে **Worker ও CRM দুপাশেই** সমর্থন যুক্ত করতে হবে।
- [ ] **ACK integrity:** CRM `sendlog` ACK দেওয়ার আগে প্রকৃত record save-এর সফলতা নিশ্চিত করা উচিত। বর্তমান implementation `saved=0` হলেও `result:true` দিতে পারে; অতএব data loss-এর ঝুঁকি আছে।
- [ ] **Idempotency:** একই device/user/original device timestamp/logindex পুনরায় পাঠালে duplicate punch insert না হয় এমন transactional uniqueness/dedup যোগ করুন।
- [ ] **Durable delivery:** HTTP failure, Worker restart বা disconnect-এর সময় lost attendance এড়াতে retry mechanism/queue/monitoring চাই। Cloudflare Queues বা DB-backed retry design বিবেচনা করুন।
- [ ] **Heartbeat + lifecycle:** অনেকক্ষণ এক socket খোলা থাকা guarantee নয়। Real device কীভাবে reconnect করে/heartbeat পাঠায় তা যাচাই করুন। Long-lived production receiver-এর জন্য Durable Objects Hibernation মূল্যায়ন করুন।
- [ ] **Privacy/logging:** employee name, device serial, biometric/attendance JSON, access secrets full payload console log করবেন না। Test screenshot share করার আগে sensitive details ঢাকুন।
- [ ] **Backups, staging, rollback:** CRM database backup; test employee; device-এর পুরোনো Server settings সংরক্ষণ।
- [ ] **Capacity:** Worker Requests/day, Worker subrequests, CRM rate limit ও actual heartbeat/connection frequency পর্যবেক্ষণ করুন।

## 14. Final Test Checklist / Handover Sheet

**এই টেবিল পূরণ করে guide-এর সঙ্গে রাখুন:**

| Test | Expected | Actual / date | Pass? |
|---|---|---|---|
| Cloudflare Worker deployed | Worker URL visible | __________ | ☐ |
| Variables saved | `CRM_URL`, `DEVICE_SN` configured | __________ | ☐ |
| HTTP route check | `426 WebSocket required` | __________ | ☐ |
| Port 8080 handshake | `101 Switching Protocols` | __________ | ☐ |
| Hoppscotch WSS connect | `Connected` | __________ | ☐ |
| Invalid serial negative test | `result:false` | __________ | ☐ |
| Valid `checklive` | `ret:checklive`, `cloudtime` | __________ | ☐ |
| Valid `reg` | `ret:reg`, `result:true` | __________ | ☐ |
| Simulated `sendlog` | ACK received | __________ | ☐ |
| CRM Sync Log saved | `records_saved > 0` | __________ | ☐ |
| Physical device connects | actual `reg` in Worker logs | __________ | ☐ |
| Physical punch reaches CRM | actual `sendlog` in Worker logs | __________ | ☐ |
| Employee Attendance updated | correct employee/date/log | __________ | ☐ |
| Retry + duplicate test | no loss / no duplicate | __________ | ☐ |
| Security acceptance | approved transport/auth | __________ | ☐ |

### Tested environment snapshot (এই Chat-এর পরীক্ষা থেকে; নতুন setup-এ copy করবেন না)

| তথ্য | পরীক্ষায় ব্যবহৃত মান |
|---|---|
| Worker name | `square-band-79cd` |
| Worker host | `square-band-79cd.abutalha101013.workers.dev` |
| WSS test URL | `wss://square-band-79cd.abutalha101013.workers.dev/pub/chat` |
| Port 8080 WS URL | `ws://square-band-79cd.abutalha101013.workers.dev:8080/pub/chat` |
| CRM receiver | `https://crm.alpha.net.bd/hr_module/aiface/receive` |
| Example device serial | `AXSE07050391` |
| Previous external test endpoint (not owned by us) | `ws://103.125.255.130:7788/pub/chat` |

**যেগুলো সত্যিই test হয়েছিল:** WSS Hoppscotch `Connected`; fake serial-এ `result:false`; valid serial-এ `checklive` + `cloudtime`; `reg` + `result:true`; Port 80 এবং 8080-এ `curl` WebSocket upgrade-এ `101 Switching Protocols`। **কোনো screenshot এখনও physical device→Cloudflare live connection বা attendance database save success প্রমাণ করেনি।**

## 15. Source Links (ভবিষ্যতে যাচাই করতে)

1. AiFace WS/WSS protocol page: <https://timyteco.net/pages/aifacewebsocket>
2. Perfex HR Module source: <https://github.com/mithunchandrasutradhar/perfex-crm-hr-module/tree/main/modules/hr_module>
3. AiFace CRM Controller (`receive`/`reg`/`checklive`/`sendlog`): <https://raw.githubusercontent.com/mithunchandrasutradhar/perfex-crm-hr-module/main/modules/hr_module/controllers/Aiface.php>
4. Attendance model (`save_aiface_log_batch`, 6-hour filter, mapping): <https://raw.githubusercontent.com/mithunchandrasutradhar/perfex-crm-hr-module/main/modules/hr_module/models/Zkteco_model.php>
5. Cloudflare Workers WebSockets: <https://developers.cloudflare.com/workers/examples/websockets/>
6. Cloudflare supported ports: <https://developers.cloudflare.com/fundamentals/reference/network-ports/>
7. Cloudflare Workers limits: <https://developers.cloudflare.com/workers/platform/limits/>
8. Cloudflare Service Bindings: <https://developers.cloudflare.com/workers/runtime-apis/bindings/service-bindings/http/>
9. Cloudflare Workers long-lived WebSockets guidance: <https://developers.cloudflare.com/workers/best-practices/workers-best-practices/>
10. Hoppscotch WebSocket tester: <https://hoppscotch.io/realtime/websocket>

---

**শেষ কথা:** এই প্রক্রিয়ায় Device-এর জন্য WebSocket এবং CRM-এর জন্য HTTP POST আলাদা রাখা হয়েছে। Browser/CMD test pass হওয়া, physical device successfully connecting হওয়া, আর CRM-এর database-এ attendance save হওয়া—এগুলো **তিনটি আলাদা success criterion**; তিনটি প্রমাণের আগে production চালু করবেন না।
