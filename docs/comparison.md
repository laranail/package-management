# Comparison

How `laranail/package-management` relates to the two projects it draws from — `nwidart/laravel-modules`
(the module engine `laranail/package-scaffolder` forks) and Botble's `plugin-management` (the plugin
loader it is informed by). It is a **runtime loader/manager**, not a generator or a CMS.

## At a glance

| | laranail/package-management | nwidart/laravel-modules | Botble plugin-management |
|---|:---:|:---:|:---:|
| Primary job | runtime **loader/manager** | **generator** + module runtime | plugin loader (inside a CMS) |
| Unit | **extension** (package \| module \| plugin) | module | plugin |
| Runtime PSR-4, no `composer dump` | + | · | + |
| Topological `require` ordering | + | · | · |
| Semver `require` + `minimum_core_version` guards | + | · | ≈ |
| Activation store | **file *or* Eloquent** (pluggable) | file (statuses.json) | DB settings (hardcoded) |
| Framework adapters | **Laravel · Lumen · Symfony** (`LoaderAdapter`) | Laravel | Laravel (CMS-bound) |
| Compiled manifest cache | + | ≈ | + |
| Lifecycle events + duck-typed hooks | **pre/post pairs** | · | ≈ |
| Install from a VCS repo (rollback-safe) | + | · | ≈ (marketplace) |
| Zero dependency required from the loaded code | + | · | · |

Legend: `+` first-class · `≈` partial · `·` not provided.

## What it keeps, and improves

- **From nwidart** — the module manifest shape (`module.json`) and the file-based statuses idea. Improved:
  the activation store is an **interface** (file default, Eloquent optional), load order is a **topological
  sort** over `require`, and the whole thing is framework-agnostic behind a `LoaderAdapter`.
- **From Botble** — the plugin.json + runtime `ClassLoader` PSR-4 + compiled-cache + lifecycle-hooks model.
  Improved: **no hardcoded DB coupling**, dependency ordering, a framework abstraction, and
  `class_exists`-guarded registration so a stale manifest never fatals the boot.

## Where it does NOT win

- **Not a generator.** It only *loads*; scaffolding new packages is [`laranail/package-scaffolder`](https://github.com/laranail/package-scaffolder)'s job.
- **Not a CMS.** No theme engine, permission registry, or admin panel — those are host concerns (it exposes
  seams; see [Host integration](tools/host-integration.md)).
- **Symfony is runtime-only.** Full compile-time DI (a Bundle + CompilerPass) is out of scope; the adapter
  registers services at runtime (see [Adapters](tools/adapters.md)).

## When NOT to use this package

- You only need to *generate* modules and are happy with nwidart's own runtime → use the scaffolder alone.
- You're inside a full CMS that already owns plugin loading, theming, and permissions → use its loader.
- You need a single Laravel package with no roles/activation/framework-portability → a plain package +
  `laranail/package-tools` is simpler.

---

[← Docs index](../README.md#documentation)
