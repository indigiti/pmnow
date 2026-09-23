# First push to `indigiti/pmnow`

The target GitHub repository is intentionally expected to be empty before the first push.

## From the prepared source ZIP

```bash
unzip PMNow_DigiOps_Ready.zip -d pmnow
cd pmnow
git init -b main
git add .
git commit -m "Prepare PMNow for DigiOps Cloudways deployment"
git remote add origin https://github.com/indigiti/pmnow.git
git push -u origin main
```

## From the provided Git bundle

```bash
git clone pmnow-main.bundle pmnow
cd pmnow
git remote add origin https://github.com/indigiti/pmnow.git
git push -u origin main
```

After push, wait for:

1. `PMNow Security and Syntax`
2. `PMNow Certified Release Artifact`

The second workflow publishes the `digiops-release` artifact expected by DigiOps. In DigiOps, use **Check update** and then **Deploy**.
