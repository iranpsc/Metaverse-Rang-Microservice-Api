# 🌐 Metaverse Rang – Microservice API

A scalable, secure, and modular **Microservice Backend System** powering the *Metaverse Rang ecosystem*, including:

- 🖼 NFT Sovereign Digital Assets
- 🎥 Video-on-Demand (VOD) Streaming
- 🌐 Metaverse Asset Infrastructure
- 🔐 Authentication & Authorization System
- ⚡ Event-driven microservices architecture

This project is designed for **high-scale Web3 applications**, decentralized media platforms, and metaverse ecosystems.

---

## 🚀 Features

- 🧩 Microservice-based architecture
- 🔐 JWT Authentication + Role-based access control
- 🖼 NFT minting & ownership verification layer
- 🎥 VOD streaming & media management system
- 🌐 Metaverse asset registry system
- ⚡ Redis caching for performance
- 📦 PostgreSQL / MongoDB support
- 🐳 Docker & Docker Compose ready
- 📡 API Gateway architecture
- 📘 Swagger/OpenAPI documentation
- 🧪 Unit & integration testing support
- 🔄 Scalable event-driven design (Kafka/RabbitMQ ready)

---

## 🏗 System Architecture

```
Client (Web / Mobile / Metaverse UI)
        │
        ▼
     API Gateway
        │
 ┌────────┬────────┬────────┬────────┐
 │        │        │        │        │
Auth     NFT      VOD     Asset   (Future Services)
Service  Service  Service  Service
 │        │        │        │
 └────── Database per Service (PostgreSQL / Redis / MongoDB) ──────┘
```

---

## 🧩 Microservices

### 🔐 Auth Service
- User registration & login
- JWT token generation
- Role & permission management
- Session handling

### 🖼 NFT Service
- NFT metadata management
- Ownership validation
- Minting system (Web3 ready)
- Asset linking

### 🎥 VOD Service
- Video upload & processing
- Streaming URL generation
- Media library management

### 🌐 Asset Service
- Metaverse digital asset registry
- NFT ↔ Asset mapping
- Ownership tracking system

---

## 📦 Project Structure

```
/src
  /gateway            → API Gateway
  /auth-service       → Authentication service
  /nft-service        → NFT management service
  /vod-service        → Video-on-demand service
  /asset-service      → Metaverse asset service

/shared               → DTOs, utils, constants
/config               → Configuration files
/docker               → Docker setup
/docs                 → API documentation
```

---

## ⚙️ Tech Stack

- Node.js / NestJS / Express
- TypeScript
- PostgreSQL
- MongoDB
- Redis
- Docker & Docker Compose
- JWT Authentication
- Swagger / OpenAPI
- Web3 / Blockchain integration ready

---

## 📥 Installation

### 1. Clone Repository

```bash
git clone https://github.com/iranpsc/Metaverse-Rang-Microservice-Api.git
cd Metaverse-Rang-Microservice-Api
```

---

### 2. Install Dependencies

```bash
npm install
```

---

### 3. Setup Environment

Create `.env` file:

```bash
touch .env
```

Add configuration:

```env
PORT=4000
NODE_ENV=development

DATABASE_URL=postgresql://user:password@localhost:5432/metaverse_db

JWT_SECRET=your_secret_key
JWT_EXPIRES_IN=7d

REDIS_URL=redis://localhost:6379
```

---

### 4. Database Setup

```bash
npx prisma migrate dev
npm run db:seed
```

---

## ▶️ Running the Project

### Development Mode

```bash
npm run dev
```

---

### Production Mode

```bash
npm run build
npm run start:prod
```

---

### Run Microservices Individually

```bash
npm run start auth-service
npm run start nft-service
npm run start vod-service
npm run start asset-service
```

---

## 🐳 Docker Setup

### Build & Run

```bash
docker-compose up --build
```

### Background Mode

```bash
docker-compose up -d
```

### Stop Services

```bash
docker-compose down
```

---

## 📡 API Gateway

Base URL:

```
http://localhost:4000
```

---

## 📘 API Documentation

Swagger UI:

```
http://localhost:4000/api/docs
```

---

## 🧪 Testing

### Run Tests

```bash
npm run test
```

### Watch Mode

```bash
npm run test:watch
```

### Coverage Report

```bash
npm run test:coverage
```

---

## 🔍 Code Quality

Lint project:

```bash
npm run lint
```

Fix issues:

```bash
npm run lint:fix
```

---

## 📊 Health Check

```http
GET /health
```

Response:

```json
{
  "status": "ok",
  "services": {
    "auth": "running",
    "nft": "running",
    "vod": "running",
    "asset": "running"
  }
}
```

---

## 🔐 Security

- JWT Authentication
- Role-based access control (RBAC)
- Rate limiting
- Input validation
- Secure environment variables
- HTTP security headers

---

## ⚡ Scalability

- Stateless microservices
- Horizontal scaling ready
- Redis caching layer
- Event-driven architecture support
- Docker/Kubernetes ready

---

## 🧠 Roadmap

- [ ] Blockchain integration (Ethereum / Polygon)
- [ ] IPFS storage for NFT metadata
- [ ] Real-time streaming optimization
- [ ] AI recommendation engine
- [ ] Multi-chain NFT support
- [ ] Metaverse 3D asset rendering engine

---

## 🤝 Contributing

We welcome contributions!

Please follow these steps:

1. Fork repository
2. Create feature branch
3. Commit changes (Conventional Commits)
4. Open Pull Request
5. Wait for review

Please read:
- `CONTRIBUTING.md`
- `CODE_OF_CONDUCT.md`

---

## 📄 License

MIT License

---

## 🌍 Vision

Metaverse Rang aims to build a **decentralized digital ownership infrastructure**, enabling users to:

- Own NFT-based digital assets
- Stream decentralized media
- Interact with metaverse environments
- Control their digital sovereignty

---

## ⭐ Support

If you like this project, please consider giving it a star ⭐ on GitHub.

---
