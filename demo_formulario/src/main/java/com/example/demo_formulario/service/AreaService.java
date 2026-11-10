package com.example.demo_formulario.service;

import java.util.List;

import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Service;
import org.springframework.web.server.ResponseStatusException;

import com.example.demo_formulario.model.Area;
import com.example.demo_formulario.repository.AreaRepository;

@Service
public class AreaService {

    private final AreaRepository repository;

    public AreaService(AreaRepository repository) {
        this.repository = repository;
    }

    public List<Area> findAll() {
        return repository.findAll();
    }

    public Area findById(Integer id) {
        return repository.findById(id)
                .orElseThrow(() -> new ResponseStatusException(HttpStatus.NOT_FOUND, "Área no encontrada"));
    }

    public Area save(Area area) {
        return repository.save(area);
    }

    public void delete(Integer id) {
        repository.delete(findById(id));
    }
}
