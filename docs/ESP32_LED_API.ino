#include <WiFi.h>
#include <WebServer.h>

const char* ssid     = "Siem Reap";
const char* password = "Welcome2SR";

IPAddress local_IP(192, 168, 1, 50);
IPAddress gateway(192, 168, 1, 1);
IPAddress subnet(255, 255, 255, 0);
IPAddress dns(192, 168, 1, 1);

#ifndef LED_BUILTIN
  #define LED_BUILTIN 2
#endif

WebServer server(80);
bool ledState = false;

void handleStatus() {
  String json = String("{\"led\":") + (ledState ? "1" : "0") + "}";
  server.send(200, "application/json", json);
}

void handleSetLed() {
  if (!server.hasArg("state")) {
    server.send(400, "application/json", "{\"error\":\"missing state (use 0 or 1)\"}");
    return;
  }

  String s = server.arg("state");
  if (s != "0" && s != "1") {
    server.send(400, "application/json", "{\"error\":\"state must be 0 or 1\"}");
    return;
  }

  ledState = (s == "1");
  digitalWrite(LED_BUILTIN, ledState ? HIGH : LOW);

  String json = String("{\"ok\":true,\"led\":") + (ledState ? "1" : "0") + "}";
  server.send(200, "application/json", json);
}

void setup() {
  Serial.begin(115200);
  delay(300);

  pinMode(LED_BUILTIN, OUTPUT);
  digitalWrite(LED_BUILTIN, LOW);

  WiFi.mode(WIFI_STA);
  if (!WiFi.config(local_IP, gateway, subnet, dns)) {
    Serial.println("Failed to configure static IP.");
  }
  WiFi.begin(ssid, password);

  Serial.print("Connecting to WiFi");
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    delay(400);
    Serial.print(".");
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("\nFailed to connect. ESP32 needs 2.4 GHz Wi-Fi.");
    return;
  }

  Serial.println("\nWiFi connected!");
  Serial.print("ESP32 IP: ");
  Serial.println(WiFi.localIP());

  // API routes
  server.on("/api/status", HTTP_GET, handleStatus);
  server.on("/api/led", HTTP_POST, handleSetLed);

  server.begin();
  Serial.println("API ready:");
  Serial.println("GET  http://<ip>/api/status");
  Serial.println("POST http://<ip>/api/led?state=1 or state=0");
}

void loop() {
  server.handleClient();
}
