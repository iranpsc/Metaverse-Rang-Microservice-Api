# 🤝 Contributing to Metaverse Rang Microservice API

First of all, thank you for your interest in contributing to **Metaverse Rang Microservice API** 🚀

This project is part of a decentralized ecosystem for **NFT assets, metaverse infrastructure, and video streaming services**. Every contribution helps improve scalability, security, and innovation.

---

## 🌍 Code of Conduct

By participating in this project, you agree to:

- Be respectful and professional
- Avoid toxic or offensive language
- Support constructive discussions
- Respect different opinions and technical approaches
- Focus on technical value, not personal criticism

---

## 🚀 How You Can Contribute

You can contribute in many ways:

### 🐛 Bug Fixes
- Fix backend or API issues
- Improve stability of microservices
- Resolve performance bottlenecks

### ✨ New Features
- NFT-related functionality
- VOD streaming improvements
- Metaverse asset enhancements
- API Gateway improvements

### 📚 Documentation
- Improve README or API docs
- Add usage examples
- Fix typos or unclear explanations

### ⚡ Performance Improvements
- Optimize database queries
- Improve caching strategy (Redis)
- Reduce API latency

### 🔐 Security Enhancements
- Improve authentication system
- Strengthen JWT implementation
- Fix vulnerabilities

---

## 📦 Getting Started

### 1. Fork the repository

Click **Fork** on GitHub.

---

### 2. Clone your fork

```bash
git clone https://github.com/YOUR_USERNAME/Metaverse-Rang-Microservice-Api.git
cd Metaverse-Rang-Microservice-Api
```

---

### 3. Create a new branch

Use a meaningful branch name:

```bash
git checkout -b feature/nft-minting-system
```

Examples:

- feature/vod-streaming
- fix/auth-token-expiry
- refactor/api-gateway
- docs/update-readme

---

### 4. Install dependencies

```bash
npm install
```

---

### 5. Setup environment

Create `.env` file:

```env
PORT=4000
NODE_ENV=development
DATABASE_URL=your_database_url
JWT_SECRET=your_secret
REDIS_URL=redis://localhost:6379
```

---

### 6. Run the project

```bash
npm run dev
```

---

## 🧪 Testing

Before submitting your changes:

```bash
npm run test
```

For coverage:

```bash
npm run test:coverage
```

Make sure all tests pass before opening a PR.

---

## 💡 Coding Standards

Please follow these guidelines:

### ✔ Clean Code
- Write readable and maintainable code
- Avoid duplication
- Use meaningful names

### ✔ Architecture Rules
- Follow microservice boundaries
- Do not mix services logic
- Keep services independent

### ✔ Commit Style (IMPORTANT)

Use **Conventional Commits**:

```bash
feat: add nft minting endpoint
fix: resolve auth token validation issue
docs: improve contributing guide
refactor: optimize vod service performance
chore: update dependencies
```

---

## 🔀 Pull Request Process

### Before opening a PR:

- Ensure code is tested
- Ensure lint passes
- Ensure no breaking changes (or clearly documented)

---

### PR Requirements:

- Clear title
- Description of changes
- Related issue reference

Example:

```text
Closes #12
```

---

### PR Template Checklist:

- [ ] Code follows project structure
- [ ] Tests added or updated
- [ ] Documentation updated
- [ ] No console errors
- [ ] Reviewed locally

---

## 🧱 Architecture Rules

This project follows Microservice principles:

- Each service must be independent
- No direct dependency between services
- Communication via API Gateway or events
- Shared code only via `/shared`

---

## 🧪 Local Development Tips

- Use Docker for consistency
- Run services independently when debugging
- Use logs for tracing microservice communication

---

## 🔐 Security Rules

- Never commit `.env` files
- Never expose secrets in code
- Validate all user inputs
- Use JWT securely
- Avoid unsafe dependencies

---

## 📡 API Changes

If you modify APIs:

- Update Swagger/OpenAPI docs
- Maintain backward compatibility if possible
- Document breaking changes clearly

---

## 🚨 Reporting Issues

If you find a bug:

Please include:

- Steps to reproduce
- Expected behavior
- Actual behavior
- Screenshots (if applicable)
- Environment details

---

## 🌱 Feature Requests

We welcome feature ideas!

Please include:

- Problem description
- Proposed solution
- Alternatives considered
- Benefits for ecosystem

---

## 🏆 Recognition

All contributors will be acknowledged.

Your contributions help build:

- A decentralized NFT ecosystem
- Scalable VOD infrastructure
- Metaverse-ready backend architecture

---

## 🚀 Thank You

Your contributions help shape the future of **Metaverse Rang**.

Together we build the next generation of **digital sovereignty infrastructure** 🌐
