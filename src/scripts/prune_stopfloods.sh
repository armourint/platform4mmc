#!/usr/bin/env bash
set -euo pipefail

echo "Commit your work before pruning: git add -A && git commit -m 'pre-prune'" 

# Remove flood domain controllers/commands/livewire
rm -f app/Http/Controllers/ModelRunController.php || true
rm -rf app/Console/Commands || true
rm -rf app/Livewire/Dashboard || true
rm -rf app/Livewire/Runs || true
rm -rf app/Livewire/Imports || true
rm -rf app/Livewire/Tides || true
rm -rf app/Livewire/Scenarios || true
rm -rf app/Livewire/Hindcasting || true

# Views (admin pages + matching livewire partials)
rm -rf resources/views/admin || true
rm -rf resources/views/livewire || true

# Config
rm -f config/stopfloods.php || true

# Flood domain migrations (keep users, cache, jobs)
for f in database/migrations/*; do
  case "$f" in
    *create_model_runs_table.php|*create_model_observations_table.php|*create_tidal_forecasts_table.php|*create_user_defined_scenarios_table.php|*create_cities_table.php|*create_locations_table.php|*create_predictions_table.php|*add_unique_index_to_predictions.php)
      echo "Removing $f"; rm -f "$f"
    ;;
  esac
done

echo "Prune complete."
