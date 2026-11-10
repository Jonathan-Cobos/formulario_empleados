package com.example.demo_formulario.repository;

import org.springframework.data.jpa.repository.JpaRepository;

import com.example.demo_formulario.model.Area;

public interface AreaRepository extends JpaRepository<Area, Integer> {
}
